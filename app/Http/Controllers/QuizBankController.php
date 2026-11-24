<?php

namespace App\Http\Controllers;

use App\Filament\Resources\QuizMeetings\QuizMeetingResource;
use App\Models\QuizMeeting;
use App\Services\QuizQuestionImport;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuizBankController extends Controller
{
    public function example(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        return response()->download(resource_path('quiz/contoh-soal.txt'), 'contoh-soal-kuis.txt');
    }

    public function store(Request $request, QuizMeeting $meeting, QuizQuestionImport $import): RedirectResponse
    {
        abort_unless($request->user()?->is_admin && QuizMeetingResource::canEdit($meeting), 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'extensions:txt', 'max:1024'],
            'seconds_per_question' => ['required', 'integer', 'between:5,120'],
        ], [], [
            'file' => 'file TXT',
            'seconds_per_question' => 'waktu per soal',
        ]);

        $questions = $import->parse($data['file']->get());

        DB::transaction(function () use ($meeting, $questions, $data): void {
            $current = QuizMeeting::query()->lockForUpdate()->findOrFail($meeting->id);
            if ($current->active_slot) {
                throw ValidationException::withMessages(['file' => 'Tutup pertemuan sebelum mengunggah bank soal baru.']);
            }

            $current->banks()->create([
                'questions' => $questions,
                'seconds_per_question' => $data['seconds_per_question'],
            ]);
        }, 3);

        Notification::make()->success()->title(count($questions).' soal disimpan')->send();

        return redirect()->route('filament.admin.resources.quiz-meetings.index');
    }
}
