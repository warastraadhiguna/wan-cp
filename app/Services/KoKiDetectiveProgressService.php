<?php

namespace App\Services;

use App\Models\KoKiProgress;
use Illuminate\Validation\ValidationException;

class KoKiDetectiveProgressService
{
    public const CASE_COUNT = 16;

    public const FINAL_CASE = 16;

    private const LEGACY_FINAL_SOLUTION = ['MOVE_FORWARD', 'MOVE_FORWARD', 'TAKE_PACKAGE', 'TURN_RIGHT', 'MOVE_FORWARD', 'MOVE_FORWARD', 'MOVE_FORWARD', 'DELIVERY'];

    public const SOLUTIONS = [
        1 => ['MOVE_FORWARD', 'MOVE_FORWARD', 'TURN_RIGHT', 'MOVE_FORWARD'],
        2 => ['FILL'],
        3 => ['UP', 'UP', 'UP'],
        4 => ['CLAP', 'TURN', 'JUMP'],
        5 => ['SPACE_TO_JUMP'],
        6 => ['RED_INPUT', 'READ_COLOR', 'CAR_STOP'],
        7 => ['DETECT_DRY', 'TAKE_WATER', 'WATER_PLANT'],
        8 => ['PERSON_APPROACHES', 'OPEN_DOOR', 'CLOSE_DOOR'],
        9 => ['GO_WAREHOUSE', 'TAKE_PACKAGE', 'GO_HOME', 'DELIVER_PACKAGE'],
        10 => ['SPACE_TO_JUMP', 'LEFT_TO_LEFT', 'RIGHT_TO_RIGHT'],
        11 => ['TEMP_HIGH', 'READ_TEMP', 'COOLER_ON'],
        12 => ['LIGHTS_OFF', 'PLAY_MOVIE', 'LIGHTS_ON'],
        13 => ['COPY', 'COPY'],
        14 => ['FLY_TARGET', 'TAKE_PHOTO', 'RETURN_BASE', 'SEND_PHOTO'],
        15 => ['PICK_ITEM', 'PACK_ITEM', 'SCAN_LABEL', 'SHIP_PACKAGE'],
        16 => self::LEGACY_FINAL_SOLUTION,
    ];

    public const COLLECTION = [
        1 => 'Wrong Direction',
        2 => 'Extra Command',
        3 => 'Missing Command',
        4 => 'Wrong Order',
        5 => 'Wrong Input',
        6 => 'Wrong Output',
        7 => 'Wrong Action',
        8 => 'Extra Action',
        9 => 'Missing Middle Step',
        10 => 'Multiple Input Mapping',
        11 => 'Wrong Cooling Output',
        12 => 'Setup Order',
        13 => 'Duplicate Command',
        14 => 'Missing Mission Step',
        15 => 'Production Order',
    ];

    public function state(KoKiProgress $progress): array
    {
        [$states, $completed] = $this->normalizedProgressData($progress);
        $meta = $states['_detective'] ?? [];
        $approved = $this->normalizeCases($progress->approved_levels ?? [1]);
        $approved[] = 1;
        array_push($approved, ...$completed);
        $approved = $this->normalizeCases($approved);
        $cases = [];

        foreach (range(1, self::CASE_COUNT) as $case) {
            $stored = $states[$case] ?? $states[(string) $case] ?? [];
            $cases[$case] = [
                'completed' => in_array($case, $completed, true),
                'attempts' => (int) ($stored['attempts'] ?? 0),
                'hint_count' => (int) ($stored['hint_count'] ?? 0),
                'program' => $stored['program'] ?? null,
                'stars' => (int) ($stored['stars'] ?? (in_array($case, $completed, true) ? 1 : 0)),
            ];
        }

        $notebookCompleted = (bool) ($meta['notebook_completed'] ?? false);
        $collectionCompleted = array_values(array_filter($completed, fn (int $case): bool => $case <= 15));

        return [
            'intro_seen' => (bool) ($meta['intro_seen'] ?? false),
            'intro_answer' => $meta['intro_answer'] ?? null,
            'completed_cases' => $completed,
            'approved_cases' => $approved,
            'current_case' => $this->currentCase($completed),
            'cases' => $cases,
            'collection' => collect(self::COLLECTION)->map(fn (string $name, int $case): array => [
                'case' => $case,
                'name' => $name,
                'unlocked' => in_array($case, $completed, true),
            ])->values()->all(),
            'collection_count' => count($collectionCompleted),
            'case_count' => count($completed),
            'final_solved' => count($completed) === self::CASE_COUNT,
            'rank' => $this->rank($completed),
            'notebook_completed' => $notebookCompleted,
            'badge' => $meta['badge'] ?? null,
            'total_stars' => array_sum(array_column($cases, 'stars')),
            'completed' => $notebookCompleted && count($completed) === self::CASE_COUNT,
        ];
    }

    public function begin(KoKiProgress $progress, string $answer): KoKiProgress
    {
        [$states] = $this->normalizedProgressData($progress);
        $states['_detective'] = [
            ...($states['_detective'] ?? []),
            'intro_seen' => true,
            'intro_answer' => $answer,
        ];
        $progress->forceFill([
            'level_states' => $states,
            'initial_answer' => ['bug_mystery' => $answer],
            'last_phase' => 'detective_case',
            'current_level' => 1,
            'approved_levels' => [1],
        ])->save();

        return $progress->refresh();
    }

    public function canAccessCase(KoKiProgress $progress, int $case): bool
    {
        $state = $this->state($progress);
        if (! $state['intro_seen']) {
            return false;
        }

        $prerequisite = $case === 1 || in_array($case - 1, $state['completed_cases'], true);

        return $prerequisite && in_array($case, $state['approved_cases'], true);
    }

    public function completeCase(KoKiProgress $progress, array $data): KoKiProgress
    {
        $case = (int) $data['case'];
        if (! $this->canAccessCase($progress, $case)) {
            throw ValidationException::withMessages(['case' => 'Case ini masih menunggu izin admin.']);
        }
        if (array_values($data['program']) !== self::SOLUTIONS[$case]) {
            throw ValidationException::withMessages(['program' => 'Perbaikan belum membuat program bekerja dengan benar.']);
        }

        [$states, $storedCompleted] = $this->normalizedProgressData($progress);
        $previous = $states[$case] ?? $states[(string) $case] ?? [];
        $states[$case] = [
            'attempts' => (int) ($previous['attempts'] ?? 0) + 1,
            'hint_count' => max((int) ($previous['hint_count'] ?? 0), (int) ($data['hint_count'] ?? 0)),
            'program' => array_values($data['program']),
            'suspects' => array_values(array_unique($data['suspects'] ?? [])),
            'solved_at' => now()->toIso8601String(),
            'test_attempts' => (int) $data['test_attempts'],
            'stars' => max(
                (int) ($previous['stars'] ?? 0),
                1 + ((int) ($data['hint_count'] ?? 0) <= 1 ? 1 : 0) + ((int) $data['test_attempts'] <= 2 ? 1 : 0),
            ),
        ];
        $completed = $this->normalizeCases([...$storedCompleted, $case]);
        $programs = $progress->level_programs ?? [];
        $programs[$case] = array_values($data['program']);
        $finalSolved = count($completed) === self::CASE_COUNT;
        $notebookCompleted = $this->state($progress)['notebook_completed'];

        $progress->forceFill([
            'level_states' => $states,
            'level_programs' => $programs,
            'last_program' => array_values($data['program']),
            'completed_levels' => $completed,
            'current_level' => $case,
            'attempt_count' => (int) $progress->attempt_count + 1,
            'last_phase' => $notebookCompleted ? 'completed' : ($finalSolved ? 'detective_notebook' : 'detective_map'),
            'completed_at' => $notebookCompleted ? ($progress->completed_at ?? now()) : null,
        ])->save();

        return $progress->refresh();
    }

    public function nextApprovableCase(KoKiProgress $progress): ?int
    {
        $state = $this->state($progress);
        foreach (range(2, self::CASE_COUNT) as $case) {
            if (! in_array($case, $state['approved_cases'], true)
                && in_array($case - 1, $state['completed_cases'], true)) {
                return $case;
            }
        }

        return null;
    }

    public function approveNextCase(KoKiProgress $progress, int $case): ?KoKiProgress
    {
        if ($this->nextApprovableCase($progress) !== $case) {
            return null;
        }
        $approved = $this->normalizeCases([...$this->state($progress)['approved_cases'], $case]);
        $progress->forceFill(['approved_levels' => $approved])->save();

        return $progress->refresh();
    }

    public function revokeLatestCase(KoKiProgress $progress): ?KoKiProgress
    {
        $state = $this->state($progress);
        $revocable = array_values(array_filter(
            $state['approved_cases'],
            fn (int $case): bool => $case > 1 && ! in_array($case, $state['completed_cases'], true),
        ));
        if ($revocable === []) {
            return null;
        }
        $latest = max($revocable);
        $progress->forceFill([
            'approved_levels' => array_values(array_filter($state['approved_cases'], fn (int $case): bool => $case !== $latest)),
        ])->save();

        return $progress->refresh();
    }

    public function completeNotebook(KoKiProgress $progress, string $fix): array
    {
        $state = $this->state($progress);
        if (! $state['final_solved']) {
            throw ValidationException::withMessages(['fix' => 'Selesaikan FINAL CASE terlebih dahulu.']);
        }

        $passed = $state['notebook_completed'] || $fix === 'TURN_RIGHT';
        $states = $progress->level_states ?? [];
        $meta = $states['_detective'] ?? [];
        $meta['notebook_attempts'] = (int) ($meta['notebook_attempts'] ?? 0) + 1;
        $meta['notebook_completed'] = $passed;
        $meta['badge'] = $passed ? 'Bug Detective' : null;
        $states['_detective'] = $meta;
        $progress->forceFill([
            'level_states' => $states,
            'exit_ticket_answers' => ['detective_notebook_fix' => $fix],
            'last_phase' => $passed ? 'completed' : 'detective_notebook',
            'completed_at' => $passed ? ($progress->completed_at ?? now()) : null,
        ])->save();

        return ['progress' => $progress->refresh(), 'passed' => $passed];
    }

    public function sync(KoKiProgress $progress, array $data): KoKiProgress
    {
        $cases = collect($data['cases'])->sortBy('case')->values();
        foreach ($cases as $caseData) {
            $case = (int) $caseData['case'];
            if (array_values($caseData['program']) !== self::SOLUTIONS[$case]) {
                continue;
            }

            $state = $this->state($progress->refresh());
            if (! in_array($case, $state['completed_cases'], true)) {
                if ($this->canAccessCase($progress, $case)) {
                    $progress = $this->completeCase($progress, $caseData);
                }

                continue;
            }

            $states = $progress->level_states ?? [];
            $stored = $states[$case] ?? $states[(string) $case] ?? [];
            $candidateStars = 1 + ((int) $caseData['hint_count'] <= 1 ? 1 : 0) + ((int) $caseData['test_attempts'] <= 2 ? 1 : 0);
            if ($candidateStars > (int) ($stored['stars'] ?? 0)) {
                $stored['stars'] = $candidateStars;
                $stored['hint_count'] = (int) $caseData['hint_count'];
                $stored['test_attempts'] = (int) $caseData['test_attempts'];
                $states[$case] = $stored;
                $progress->forceFill(['level_states' => $states])->save();
            }
        }

        if (($data['notebook_fix'] ?? null) === 'TURN_RIGHT' && $this->state($progress->refresh())['final_solved']) {
            $progress = $this->completeNotebook($progress, 'TURN_RIGHT')['progress'];
        }

        return $progress->refresh();
    }

    private function currentCase(array $completed): int
    {
        foreach (range(1, self::CASE_COUNT) as $case) {
            if (! in_array($case, $completed, true)) {
                return $case;
            }
        }

        return self::CASE_COUNT;
    }

    /** @return array{0: array, 1: list<int>} */
    private function normalizedProgressData(KoKiProgress $progress): array
    {
        $states = $progress->level_states ?? [];
        $completed = $this->normalizeCases($progress->completed_levels ?? []);
        $legacyCase = collect([11, 6])->first(function (int $case) use ($states, $completed): bool {
            $program = $states[$case]['program'] ?? $states[(string) $case]['program'] ?? null;

            return in_array($case, $completed, true)
                && is_array($program)
                && array_values($program) === self::LEGACY_FINAL_SOLUTION;
        });
        $legacyFinal = $legacyCase !== null && ! in_array(self::FINAL_CASE, $completed, true);

        if ($legacyFinal) {
            $states[self::FINAL_CASE] = $states[$legacyCase] ?? $states[(string) $legacyCase];
            unset($states[$legacyCase], $states[(string) $legacyCase]);
            $completed = $this->normalizeCases([
                ...array_values(array_filter($completed, fn (int $case): bool => $case !== $legacyCase)),
                self::FINAL_CASE,
            ]);
        }

        return [$states, $completed];
    }

    private function rank(array $completed): string
    {
        if (collect(range(1, 15))->every(fn (int $case): bool => in_array($case, $completed, true))) return 'Chief Bug Detective';
        if (collect(range(1, 10))->every(fn (int $case): bool => in_array($case, $completed, true))) return 'Master Detective';
        if (collect(range(1, 9))->every(fn (int $case): bool => in_array($case, $completed, true))) return 'Senior Bug Detective';
        if (collect(range(1, 6))->every(fn (int $case): bool => in_array($case, $completed, true))) return 'Bug Investigator';
        if (collect(range(1, 3))->every(fn (int $case): bool => in_array($case, $completed, true))) return 'Junior Detective';

        return 'Detective Trainee';
    }

    /** @return list<int> */
    private function normalizeCases(array $cases): array
    {
        $cases = array_values(array_unique(array_filter(array_map('intval', $cases), fn (int $case): bool => $case >= 1 && $case <= self::CASE_COUNT)));
        sort($cases);

        return $cases;
    }
}
