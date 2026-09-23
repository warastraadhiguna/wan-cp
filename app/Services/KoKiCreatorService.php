<?php

namespace App\Services;

use App\Models\KoKiProgress;
use Illuminate\Validation\ValidationException;

class KoKiCreatorService
{
    public const SETUP_COUNT = 4;

    public const PLAYTEST_APPROVAL = 5;

    public const DEFAULTS = [
        'stage' => 0,
        'character' => 'koki',
        'theme' => 'green',
        'controls' => ['left' => 'SPACE', 'right' => 'LEFT', 'jump' => 'RIGHT'],
        'collectible' => 'leaf',
        'collect_rule' => 'none',
        'obstacle' => 'rock',
        'obstacle_rule' => 'start',
        'target_score' => 3,
        'tested_win' => false,
        'playtested' => false,
        'playtest_checks' => ['control' => false, 'collectible' => false, 'score' => false, 'obstacle' => false, 'win' => false],
        'bonuses' => ['second_collectible' => false, 'special_plus_three' => false, 'second_obstacle' => false, 'sound' => false, 'timer' => false, 'target' => false],
    ];

    public function state(KoKiProgress $progress): array
    {
        return array_replace_recursive(self::DEFAULTS, ($progress->level_states ?? [])['_creator'] ?? []);
    }

    public function adventureState(KoKiProgress $progress): array
    {
        return array_replace([
            'completed' => false,
            'completed_levels' => [],
            'answers' => [],
            'medal' => null,
        ], ($progress->level_states ?? [])['_creator_adventure'] ?? []);
    }

    public function approvedSteps(KoKiProgress $progress): array
    {
        $approved = array_map('intval', $progress->approved_levels ?? []);
        $completedSetup = min(self::SETUP_COUNT, (int) $this->state($progress)['stage']);
        if ($completedSetup > 0) {
            array_push($approved, ...range(1, $completedSetup));
        }
        if ($this->state($progress)['playtested'] || $this->adventureState($progress)['completed']) {
            $approved[] = self::PLAYTEST_APPROVAL;
        }
        $approved = array_values(array_unique(array_filter(
            $approved,
            fn (int $step): bool => $step >= 1 && $step <= self::PLAYTEST_APPROVAL,
        )));
        sort($approved);

        return $approved;
    }

    public function canEnterStep(KoKiProgress $progress, int $step): bool
    {
        return in_array($step, $this->approvedSteps($progress), true);
    }

    public function canCompleteSetup(KoKiProgress $progress, int $targetStage): bool
    {
        $currentStage = (int) $this->state($progress)['stage'];
        if ($targetStage <= $currentStage) {
            return true;
        }

        return $targetStage === $currentStage + 1
            && $targetStage <= self::SETUP_COUNT
            && $this->canEnterStep($progress, $targetStage);
    }

    public function playtestApproved(KoKiProgress $progress): bool
    {
        if ($this->adventureState($progress)['completed'] || $this->state($progress)['playtested']) {
            return true;
        }

        return $this->canEnterStep($progress, self::PLAYTEST_APPROVAL);
    }

    public function nextApprovableStep(KoKiProgress $progress): ?int
    {
        $stage = min(self::SETUP_COUNT, (int) $this->state($progress)['stage']);
        $next = min(self::PLAYTEST_APPROVAL, $stage + 1);

        return in_array($next, $this->approvedSteps($progress), true) ? null : $next;
    }

    public function canApproveNextStep(KoKiProgress $progress): bool
    {
        return $this->nextApprovableStep($progress) !== null
            && ! $this->adventureState($progress)['completed'];
    }

    public function approveNextStep(KoKiProgress $progress): ?KoKiProgress
    {
        $next = $this->nextApprovableStep($progress);
        if ($next === null) {
            return null;
        }

        $approved = array_values(array_unique([
            ...array_map('intval', $progress->approved_levels ?? []),
            $next,
        ]));
        sort($approved);
        $progress->forceFill(['approved_levels' => $approved])->save();

        return $progress->refresh();
    }

    public function revokeLatestApproval(KoKiProgress $progress): ?KoKiProgress
    {
        if ($this->adventureState($progress)['completed']) {
            return null;
        }

        $approved = array_map('intval', $progress->approved_levels ?? []);
        $stage = (int) $this->state($progress)['stage'];
        $revocable = array_values(array_filter(
            $approved,
            fn (int $step): bool => $step > $stage,
        ));
        if ($revocable === []) {
            return null;
        }
        $latest = max($revocable);

        $progress->forceFill([
            'approved_levels' => array_values(array_filter(
                $approved,
                fn (int $approval): bool => $approval !== $latest,
            )),
        ])->save();

        return $progress->refresh();
    }

    public function completeAdventure(KoKiProgress $progress, array $data): KoKiProgress
    {
        $states = $progress->level_states ?? [];
        $states['_creator_adventure'] = [
            'completed' => true,
            'completed_levels' => range(1, 10),
            'answers' => $data['answers'],
            'medal' => 'Ko-Ki Game Creator',
            'completed_at' => now()->toIso8601String(),
        ];

        $progress->forceFill([
            'level_states' => $states,
            'completed_levels' => range(1, 10),
            'current_level' => 10,
            'last_phase' => 'completed',
            'completed_at' => $progress->completed_at ?? now(),
        ])->save();

        return $progress->refresh();
    }

    public function save(KoKiProgress $progress, array $data): KoKiProgress
    {
        $stage = (int) $data['stage'];
        $previous = $this->state($progress);
        // Editor bekerja lokal. Server menerima snapshot pada checkpoint besar,
        // termasuk loncatan langsung dari tahap awal ke empat tantangan selesai.
        if ($stage < (int) $previous['stage']) {
            return $progress;
        }
        if ($previous['playtested'] && ! ($data['playtested'] ?? false)) {
            return $progress;
        }
        if (count(array_unique($data['controls'])) !== 3) {
            throw ValidationException::withMessages(['controls' => 'Setiap aksi membutuhkan tombol yang berbeda.']);
        }
        if ($stage >= 1 && $data['controls'] !== ['left' => 'LEFT', 'right' => 'RIGHT', 'jump' => 'SPACE']) {
            throw ValidationException::withMessages(['controls' => 'Perbaiki mapping kontrol sebelum lanjut.']);
        }
        if ($stage >= 2 && $data['collect_rule'] !== 'score_move') {
            throw ValidationException::withMessages(['collect_rule' => 'Aturan collectible perlu dipasang dahulu.']);
        }
        if ($data['tested_win'] && $stage < 4) {
            throw ValidationException::withMessages(['tested_win' => 'Selesaikan empat tantangan dahulu.']);
        }
        $checks = array_replace(self::DEFAULTS['playtest_checks'], $data['playtest_checks'] ?? []);
        if (($data['playtested'] ?? false) && ($stage < 4 || in_array(false, $checks, true))) {
            throw ValidationException::withMessages(['playtested' => 'Mainkan dan periksa semua bagian gim dahulu.']);
        }
        if (($data['playtested'] ?? false) && ! ($data['tested_win'] || $previous['tested_win'])) {
            throw ValidationException::withMessages(['playtested' => 'Uji kondisi menang sebelum Playtest selesai.']);
        }
        $bonuses = array_replace(self::DEFAULTS['bonuses'], $data['bonuses'] ?? []);
        if (in_array(true, $bonuses, true) && ! ($data['playtested'] ?? $previous['playtested'])) {
            throw ValidationException::withMessages(['bonuses' => 'Buka bonus setelah Playtest selesai.']);
        }
        if ($bonuses['special_plus_three'] && ! $bonuses['second_collectible']) {
            throw ValidationException::withMessages(['bonuses' => 'Tambahkan collectible kedua dahulu.']);
        }
        if ($data['target_score'] === 15 && ! $bonuses['target']) {
            throw ValidationException::withMessages(['target_score' => 'Target 15 poin adalah bonus Creator Challenge.']);
        }

        $states = $progress->level_states ?? [];
        $states['_creator'] = [
            ...$previous,
            ...$data,
            'stage' => max((int) $previous['stage'], $stage),
            'tested_win' => (bool) ($previous['tested_win'] || $data['tested_win']),
            'playtested' => (bool) ($previous['playtested'] || ($data['playtested'] ?? false)),
            'playtest_checks' => $checks,
            'bonuses' => $bonuses,
        ];
        $adventureCompleted = (bool) (($states['_creator_adventure']['completed'] ?? false));
        $completed = $states['_creator']['playtested'] || $adventureCompleted;
        $progress->forceFill([
            'level_states' => $states,
            'completed_levels' => $adventureCompleted ? range(1, 10) : ($states['_creator']['stage'] > 0 ? range(1, $states['_creator']['stage']) : []),
            'current_level' => $adventureCompleted ? 10 : max(1, $states['_creator']['stage']),
            'last_phase' => $completed ? 'completed' : ($stage >= 4 ? 'playtest' : 'creator_lab'),
            'completed_at' => $completed ? ($progress->completed_at ?? now()) : $progress->completed_at,
        ])->save();

        return $progress->refresh();
    }
}
