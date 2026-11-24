<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\QuizMeeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $meeting = QuizMeeting::query()->active()->with('currentBank')->first();
        $bank = $meeting?->currentBank;
        $attempt = $bank?->attempts()->where('player_key', $this->playerKey($request))->first();

        return view('tools.quiz.index', compact('meeting', 'bank', 'attempt'));
    }

    public function join(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank' => ['required', 'integer'],
            'nickname' => ['required', 'string', 'max:30'],
        ]);

        $attempt = DB::transaction(function () use ($request, $data): ?QuizAttempt {
            $meeting = QuizMeeting::query()->active()->lockForUpdate()->first();
            $bank = $meeting?->currentBank;

            if (! $bank || $bank->id !== (int) $data['bank']) {
                return null;
            }

            return $bank->attempts()->firstOrCreate(
                ['player_key' => $this->playerKey($request)],
                ['nickname' => $data['nickname'], 'answers' => []],
            );
        }, 3);

        return $attempt
            ? $this->resume($attempt)
            : to_route('tools.quiz.index')->with('status', 'Pertemuan sudah berubah. Pilih kuis yang tersedia.');
    }

    public function play(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->ensureOwner($request, $attempt);

        if ($attempt->completed_at) {
            return $this->resume($attempt);
        }

        $bank = $attempt->bank;
        $meeting = $bank->meeting;

        if (! $meeting->active_slot || $meeting->currentBank?->id !== $bank->id) {
            return $this->closed();
        }

        $questions = $bank->questions;
        $answers = $attempt->answers;
        $position = count($answers);
        $remaining = $attempt->question_started_ms === null
            ? null
            : max(0, $attempt->question_started_ms + $bank->seconds_per_question * 1000 - now()->getTimestampMs());
        $question = $remaining === null ? null : $questions[$position];
        $previous = $position > 0 ? $answers[$position - 1] : null;
        $previousCorrectOption = $previous === null ? null : $questions[$position - 1]['options'][$questions[$position - 1]['answer']];
        $total = count($questions);

        return view('tools.quiz.play', compact('attempt', 'bank', 'meeting', 'position', 'remaining', 'question', 'previous', 'previousCorrectOption', 'total'));
    }

    public function next(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        return $this->playAction($request, $attempt, function (QuizAttempt $current): void {
            if ($current->question_started_ms === null) {
                $current->question_started_ms = now()->getTimestampMs();
                $current->save();
            }
        });
    }

    public function answer(Request $request, QuizAttempt $attempt): RedirectResponse
    {
        $choice = $request->validate(['choice' => ['nullable', 'in:A,B,C,D']])['choice'] ?? null;
        $receivedAt = now();

        return $this->playAction($request, $attempt, function (QuizAttempt $current) use ($choice, $receivedAt): void {
            if ($current->question_started_ms === null) {
                return;
            }

            $bank = $current->bank;
            $answers = $current->answers;
            $elapsed = max(0, $receivedAt->getTimestampMs() - $current->question_started_ms);
            $limit = $bank->seconds_per_question * 1000;

            if ($choice === null && $elapsed < $limit) {
                return;
            }

            $choice = $elapsed < $limit ? $choice : null;
            $correct = $choice !== null && $choice === $bank->questions[count($answers)]['answer'];
            $answers[] = ['choice' => $choice, 'correct' => $correct, 'elapsed_ms' => min($elapsed, $limit)];

            $current->answers = $answers;
            $current->question_started_ms = null;
            if ($correct) {
                $current->correct_count++;
                $current->correct_time_ms += $elapsed;
            }
            if (count($answers) === count($bank->questions)) {
                $current->completed_at = $receivedAt;
            }
            $current->save();
        });
    }

    public function results(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->ensureOwner($request, $attempt);

        if (! $attempt->completed_at) {
            return $this->resume($attempt);
        }

        $bank = $attempt->bank;
        $meeting = $bank->meeting;
        $leaderboard = $bank->attempts()->completed()->ranked()->limit(10)
            ->get(['id', 'nickname', 'correct_count', 'correct_time_ms']);
        $rank = $bank->attempts()->completed()->where(function (Builder $query) use ($attempt): void {
            $query->where('correct_count', '>', $attempt->correct_count)
                ->orWhere(function (Builder $query) use ($attempt): void {
                    $query->where('correct_count', $attempt->correct_count)
                        ->where(function (Builder $query) use ($attempt): void {
                            $query->where('correct_time_ms', '<', $attempt->correct_time_ms)
                                ->orWhere(function (Builder $query) use ($attempt): void {
                                    $query->where('correct_time_ms', $attempt->correct_time_ms)->where('id', '<', $attempt->id);
                                });
                        });
                });
        })->count() + 1;

        return view('tools.quiz.results', compact('attempt', 'bank', 'meeting', 'leaderboard', 'rank'));
    }

    private function playAction(Request $request, QuizAttempt $attempt, callable $update): RedirectResponse
    {
        $this->ensureOwner($request, $attempt);
        $data = $request->validate(['position' => ['required', 'integer', 'min:0']]);
        $bank = $attempt->bank;

        $current = DB::transaction(function () use ($attempt, $bank, $data, $update): ?QuizAttempt {
            $meeting = QuizMeeting::query()->sharedLock()->findOrFail($bank->quiz_meeting_id);
            $current = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($current->completed_at) {
                return $current;
            }
            if (! $meeting->active_slot || $meeting->currentBank?->id !== $bank->id) {
                return null;
            }
            if (count($current->answers) === (int) $data['position']) {
                $current->setRelation('bank', $bank);
                $update($current);
            }

            return $current;
        }, 3);

        return $current ? $this->resume($current) : $this->closed();
    }

    private function playerKey(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }

    private function ensureOwner(Request $request, QuizAttempt $attempt): void
    {
        abort_unless(hash_equals($attempt->player_key, $this->playerKey($request)), 404);
    }

    private function resume(QuizAttempt $attempt): RedirectResponse
    {
        return to_route($attempt->completed_at ? 'tools.quiz.results' : 'tools.quiz.play', $attempt);
    }

    private function closed(): RedirectResponse
    {
        return to_route('tools.quiz.index')->with('status', 'Pertemuan ini sedang ditutup oleh pengajar.');
    }
}
