<?php

namespace App\Services;

use App\Models\KoKiProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class KoKiProgressService
{
    public const SESSION_KEY = 'ko_ki.progress_id';

    public function current(Request $request): ?KoKiProgress
    {
        $progressId = $request->session()->get(self::SESSION_KEY);

        if (! is_numeric($progressId)) {
            return null;
        }

        return KoKiProgress::query()->find((int) $progressId);
    }

    public function start(Request $request, array $data): KoKiProgress
    {
        $progress = KoKiProgress::query()->create([
            'user_id' => null,
            'student_name' => $data['student_name'],
            'grade' => (int) $data['grade'],
            'last_phase' => 'opening',
            'current_level' => 1,
            'approved_levels' => [],
            'completed_levels' => [],
            'attempt_count' => 0,
            'level_attempts' => [],
            'last_program' => [],
            'level_programs' => [],
            'predictions' => [],
            'level_states' => [],
            'started_at' => now(),
        ]);

        $request->session()->put(self::SESSION_KEY, $progress->getKey());

        return $progress;
    }

    public function update(KoKiProgress $progress, array $data): KoKiProgress
    {
        $progress->fill(Arr::only($data, [
            'last_phase',
            'current_level',
            'last_program',
        ]));

        $currentLevel = (int) ($data['current_level'] ?? $progress->current_level ?? 1);

        if (array_key_exists('last_program', $data)) {
            $levelPrograms = $progress->level_programs ?? [];
            $levelPrograms[$currentLevel] = $data['last_program'];
            $progress->level_programs = $levelPrograms;
        }

        if ($data['increment_attempt'] ?? false) {
            $progress->attempt_count++;

            $levelAttempts = $progress->level_attempts ?? [];
            $levelAttempts[$currentLevel] = ($levelAttempts[$currentLevel] ?? 0) + 1;
            $progress->level_attempts = $levelAttempts;
        }

        if (array_key_exists('prediction', $data)) {
            $predictions = $progress->predictions ?? [];
            $predictions[$currentLevel] = $data['prediction'];
            $progress->predictions = $predictions;
        }

        if (array_key_exists('level_state', $data)) {
            $levelStates = $progress->level_states ?? [];
            $levelStates[$currentLevel] = [
                ...($levelStates[$currentLevel] ?? []),
                ...$data['level_state'],
            ];
            $progress->level_states = $levelStates;
        }

        if (array_key_exists('completed_level', $data)) {
            $completedLevels = $progress->completed_levels ?? [];
            $completedLevels[] = $data['completed_level'];
            $progress->completed_levels = array_values(array_unique($completedLevels));
        }

        $progress->save();

        return $progress->refresh();
    }

    /** @return list<int> */
    public function approvedLevels(KoKiProgress $progress): array
    {
        $approvedLevels = $progress->approved_levels;

        // Progress lama dibuat sebelum fitur persetujuan tersedia.
        if ($approvedLevels === null) {
            $approvedLevels = range(1, max(1, (int) $progress->current_level));
        }

        $approvedLevels = array_map('intval', $approvedLevels);
        $approvedLevels = array_values(array_unique(array_filter(
            $approvedLevels,
            fn (int $level): bool => $level >= 1 && $level <= 4,
        )));
        sort($approvedLevels);

        return $approvedLevels;
    }

    public function canAccessLevel(KoKiProgress $progress, int $level): bool
    {
        return in_array($level, $this->approvedLevels($progress), true);
    }

    public function approveNextLevel(KoKiProgress $progress, int $targetLevel): ?KoKiProgress
    {
        $approvedLevels = $this->approvedLevels($progress);
        $expectedLevel = $approvedLevels === [] ? 1 : max($approvedLevels) + 1;
        $completedLevels = array_map('intval', $progress->completed_levels ?? []);

        $previousLevelIsComplete = $targetLevel === 1
            || in_array($targetLevel - 1, $completedLevels, true);

        if ($targetLevel !== $expectedLevel || ! $previousLevelIsComplete) {
            return null;
        }

        $approvedLevels[] = $targetLevel;
        $progress->forceFill([
            'approved_levels' => array_values(array_unique($approvedLevels)),
            'current_level' => $targetLevel,
        ])->save();

        return $progress->refresh();
    }

    public function revokeLatestLevel(KoKiProgress $progress): ?KoKiProgress
    {
        $approvedLevels = $this->approvedLevels($progress);

        if ($approvedLevels === []) {
            return null;
        }

        $latestLevel = max($approvedLevels);

        if (in_array($latestLevel, $progress->completed_levels ?? [], true)) {
            return null;
        }

        $approvedLevels = array_values(array_filter(
            $approvedLevels,
            fn (int $level): bool => $level !== $latestLevel,
        ));

        $progress->forceFill([
            'approved_levels' => $approvedLevels,
            'current_level' => $approvedLevels === []
                ? 1
                : min((int) $progress->current_level, max($approvedLevels)),
        ])->save();

        return $progress->refresh();
    }

    public function setPhase(KoKiProgress $progress, string $phase): KoKiProgress
    {
        $progress->forceFill(['last_phase' => $phase])->save();

        return $progress->refresh();
    }

    public function saveInitialAnswer(KoKiProgress $progress, int $questionNumber, string $answer): KoKiProgress
    {
        $answers = $progress->initial_answer ?? [];
        $answers[$questionNumber] = $answer;

        $progress->forceFill([
            'initial_answer' => $answers,
            'last_phase' => $questionNumber === 4 ? 'questions_completed' : 'question_'.($questionNumber + 1),
        ])->save();

        return $progress->refresh();
    }

    public function complete(KoKiProgress $progress, array $data): KoKiProgress
    {
        $progress->forceFill([
            'last_phase' => 'completed',
            'exit_ticket' => 'Belum tepat; Benar; Belum tepat',
            'exit_ticket_answers' => $data['exit_ticket_answers'],
            'reflection' => $data['reflection'],
            'reflection_choice' => $data['reflection_choice'],
            'completed_at' => $progress->completed_at ?? now(),
        ])->save();

        return $progress->refresh();
    }
}
