<?php

namespace App\Services;

use App\Models\KoKiProgress;
use Illuminate\Validation\ValidationException;

class KoKiRescueProgressService
{
    public const MISSION_COUNT = 6;

    public const COMMAND_LIMITS = [1 => 12, 2 => 23, 3 => 21, 4 => 20, 5 => 20, 6 => 42];

    public const EFFICIENT_COMMANDS = [1 => 7, 2 => 19, 3 => 20, 4 => 17, 5 => 17, 6 => 34];

    public const FINAL_POSITIONS = [
        1 => ['x' => 2, 'y' => 2], 2 => ['x' => 4, 'y' => 1], 3 => ['x' => 6, 'y' => 1],
        4 => ['x' => 4, 'y' => 1], 5 => ['x' => 5, 'y' => 0], 6 => ['x' => 0, 'y' => 6],
    ];

    public function state(KoKiProgress $progress): array
    {
        $levelStates = $progress->level_states ?? [];
        $meta = $levelStates['_rescue'] ?? [];
        $completed = $this->normalizeLevels($progress->completed_levels ?? []);
        $approved = $this->normalizeLevels($progress->approved_levels ?? [1]);
        $approved[] = 1;
        array_push($approved, ...$completed);
        $approved = $this->normalizeLevels($approved);
        $levels = [];

        foreach (range(1, self::MISSION_COUNT) as $level) {
            $stored = $levelStates[$level] ?? $levelStates[(string) $level] ?? [];
            $levels[$level] = [
                'completed' => in_array($level, $completed, true),
                'stars' => (int) ($stored['stars'] ?? 0),
                'command_count' => (int) ($stored['command_count'] ?? 0),
                'attempts' => (int) ($stored['attempts'] ?? 0),
            ];
        }

        return [
            'intro_seen' => (bool) ($meta['intro_seen'] ?? false),
            'approved_levels' => $approved,
            'completed_levels' => $completed,
            'current_level' => (int) ($progress->current_level ?? 1),
            'level_progress' => $levels,
            'final_completed' => in_array(6, $completed, true),
            'evaluation_attempts' => (int) ($meta['evaluation_attempts'] ?? 0),
            'evaluation_passed' => (bool) ($meta['evaluation_passed'] ?? false),
            'medal' => $meta['medal'] ?? null,
            'total_stars' => array_sum(array_column($levels, 'stars')),
        ];
    }

    public function completeIntro(KoKiProgress $progress): KoKiProgress
    {
        $states = $progress->level_states ?? [];
        $states['_rescue'] = [...($states['_rescue'] ?? []), 'intro_seen' => true];
        $progress->forceFill(['level_states' => $states, 'last_phase' => 'rescue_map', 'approved_levels' => [1]])->save();

        return $progress->refresh();
    }

    public function canAccessLevel(KoKiProgress $progress, int $level): bool
    {
        $state = $this->state($progress);
        $prerequisite = $level === 1 || in_array($level - 1, $state['completed_levels'], true);

        return $prerequisite && in_array($level, $state['approved_levels'], true);
    }

    public function completeLevel(KoKiProgress $progress, array $data): array
    {
        $level = (int) $data['level'];
        if (! $this->canAccessLevel($progress, $level)) {
            throw ValidationException::withMessages(['level' => 'Level ini masih menunggu izin admin.']);
        }
        if (count($data['program']) > self::COMMAND_LIMITS[$level]) {
            throw ValidationException::withMessages(['program' => 'Program melebihi kapasitas baterai level ini.']);
        }

        $predictionCorrect = $data['prediction'] === self::FINAL_POSITIONS[$level];
        $stars = 1 + ($predictionCorrect ? 1 : 0) + (count($data['program']) <= self::EFFICIENT_COMMANDS[$level] ? 1 : 0);
        $states = $progress->level_states ?? [];
        $previous = $states[$level] ?? $states[(string) $level] ?? [];
        $states[$level] = [
            'stars' => max((int) ($previous['stars'] ?? 0), $stars),
            'command_count' => (int) (($previous['command_count'] ?? PHP_INT_MAX) <= count($data['program']) ? $previous['command_count'] : count($data['program'])),
            'attempts' => (int) ($previous['attempts'] ?? 0) + 1,
            'prediction' => $data['prediction'],
            'program' => array_values($data['program']),
        ];
        $completed = $this->normalizeLevels([...($progress->completed_levels ?? []), $level]);
        $programs = $progress->level_programs ?? [];
        $programs[$level] = array_values($data['program']);
        $predictions = $progress->predictions ?? [];
        $predictions[$level] = $data['prediction'];
        $progress->forceFill([
            'level_states' => $states,
            'level_programs' => $programs,
            'predictions' => $predictions,
            'completed_levels' => $completed,
            'current_level' => $level,
            'attempt_count' => (int) $progress->attempt_count + 1,
            'last_phase' => $level === 6 ? 'rescue_evaluation' : 'rescue_map',
        ])->save();

        return ['progress' => $progress->refresh(), 'stars' => $stars, 'prediction_correct' => $predictionCorrect];
    }

    public function nextApprovableLevel(KoKiProgress $progress): ?int
    {
        $state = $this->state($progress);
        foreach (range(2, self::MISSION_COUNT) as $level) {
            if (! in_array($level, $state['approved_levels'], true)
                && in_array($level - 1, $state['completed_levels'], true)) {
                return $level;
            }
        }

        return null;
    }

    public function approveNextLevel(KoKiProgress $progress, int $level): ?KoKiProgress
    {
        if ($this->nextApprovableLevel($progress) !== $level) {
            return null;
        }
        $progress->forceFill(['approved_levels' => $this->normalizeLevels([...$this->state($progress)['approved_levels'], $level])])->save();

        return $progress->refresh();
    }

    public function revokeLatestLevel(KoKiProgress $progress): ?KoKiProgress
    {
        $state = $this->state($progress);
        $revocable = array_values(array_filter($state['approved_levels'], fn (int $level): bool => $level > 1 && ! in_array($level, $state['completed_levels'], true)));
        if ($revocable === []) {
            return null;
        }
        $latest = max($revocable);
        $progress->forceFill(['approved_levels' => array_values(array_filter($state['approved_levels'], fn (int $level): bool => $level !== $latest))])->save();

        return $progress->refresh();
    }

    public function submitEvaluation(KoKiProgress $progress, array $answers): array
    {
        if (! in_array(6, $this->state($progress)['completed_levels'], true)) {
            throw ValidationException::withMessages(['evaluation' => 'Selesaikan Emergency Garden terlebih dahulu.']);
        }
        $correct = $answers['clear_instruction'] === 'ikuti_persis'
            && $answers['missing_command'] === 'ambil_air'
            && $answers['sequence'] === 'ambil-maju-siram';
        $states = $progress->level_states ?? [];
        $meta = $states['_rescue'] ?? [];
        $meta['evaluation_attempts'] = (int) ($meta['evaluation_attempts'] ?? 0) + 1;
        $meta['evaluation_passed'] = $correct;
        $meta['medal'] = $correct ? 'Komandan Rescue Ko-Ki' : null;
        $states['_rescue'] = $meta;
        $progress->forceFill([
            'level_states' => $states,
            'exit_ticket_answers' => $answers,
            'last_phase' => $correct ? 'completed' : 'rescue_evaluation',
            'completed_at' => $correct ? ($progress->completed_at ?? now()) : null,
        ])->save();

        return ['progress' => $progress->refresh(), 'passed' => $correct, 'medal' => $meta['medal']];
    }

    /** @return list<int> */
    private function normalizeLevels(array $levels): array
    {
        $levels = array_values(array_unique(array_filter(array_map('intval', $levels), fn (int $level): bool => $level >= 1 && $level <= self::MISSION_COUNT)));
        sort($levels);

        return $levels;
    }
}
