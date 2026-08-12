<?php

namespace App\Services;

use App\Models\KoKiProgress;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class KoKiAdventureService
{
    public const ADVENTURE_VERSION = 4;

    public const MISSION_COUNT = 5;

    public const GRID_WIDTH = 10;

    public const GRID_HEIGHT = 7;

    public const LEVELS = [
        1 => [
            'name' => 'Training', 'icon' => '🎓', 'target' => 1, 'timer' => null, 'minutes' => 6,
            'description' => 'Buat satu Jus Apel sambil mengenal semua alat pabrik.',
            'systems' => [], 'orders' => 1,
        ],
        2 => [
            'name' => 'Juice Rush', 'icon' => '⏱️', 'target' => 5, 'timer' => 240, 'minutes' => 6,
            'description' => 'Selesaikan lima order sebelum waktu empat menit habis.',
            'systems' => ['timer', 'combo'], 'orders' => 1,
        ],
        3 => [
            'name' => 'Conveyor Chaos', 'icon' => '🏭', 'target' => 6, 'timer' => null, 'minutes' => 8,
            'description' => 'Ambil bahan dari conveyor sambil menghindari robot pembersih.',
            'systems' => ['conveyor', 'obstacle', 'combo'], 'orders' => 1,
        ],
        4 => [
            'name' => 'Double Order', 'icon' => '✌️', 'target' => 6, 'timer' => null, 'minutes' => 8,
            'target_score' => 900, 'minimum_batches' => 3,
            'description' => 'Dua order aktif. Tentukan sendiri order yang ingin diselesaikan dulu.',
            'systems' => ['double_order', 'combo'], 'orders' => 2,
        ],
        5 => [
            'name' => 'Factory Frenzy', 'icon' => '⚡', 'target' => 8, 'timer' => 480, 'minutes' => 12,
            'target_score' => 1800, 'minimum_batches' => 4,
            'description' => 'Gabungkan semua kemampuan dan selesaikan delapan order.',
            'systems' => ['timer', 'conveyor', 'obstacle', 'double_order', 'combo', 'powerups'], 'orders' => 2,
        ],
    ];

    public const INGREDIENTS = [
        'apple' => ['label' => 'Apel', 'icon' => '🍎'],
        'banana' => ['label' => 'Pisang', 'icon' => '🍌'],
        'water' => ['label' => 'Air', 'icon' => '💧'],
    ];

    public const PRODUCTS = [
        'apple_juice' => ['label' => 'Jus Apel', 'icon' => '🧃'],
        'banana_juice' => ['label' => 'Jus Pisang', 'icon' => '🥤'],
        'mixed_juice' => ['label' => 'Jus Campur', 'icon' => '🍹'],
        'mystery' => ['label' => 'Gumpalan Misterius', 'icon' => '🟣'],
    ];

    public const RECIPES = [
        'apple_juice' => ['apple', 'water'],
        'banana_juice' => ['banana', 'water'],
        'mixed_juice' => ['apple', 'banana', 'water'],
    ];

    public const ORDER_SEQUENCE = [
        'banana_juice', 'apple_juice', 'mixed_juice', 'apple_juice', 'banana_juice', 'mixed_juice',
    ];

    public const POWER_UPS = [
        'speed_boost' => ['label' => 'Speed Boost', 'icon' => '⚡', 'seconds' => 12],
        'big_bag' => ['label' => 'Big Bag', 'icon' => '🎒', 'seconds' => 18],
        'time_bonus' => ['label' => 'Time Bonus', 'icon' => '⏰', 'seconds' => 20],
    ];

    public const STATIONS = [
        'apple' => ['type' => 'ingredient', 'x' => 1, 'y' => 1, 'label' => 'Apel', 'icon' => '🍎'],
        'banana' => ['type' => 'ingredient', 'x' => 1, 'y' => 3, 'label' => 'Pisang', 'icon' => '🍌'],
        'water' => ['type' => 'ingredient', 'x' => 1, 'y' => 5, 'label' => 'Air', 'icon' => '💧'],
        'blender' => ['type' => 'blender', 'x' => 5, 'y' => 1, 'label' => 'Blender', 'icon' => '⚙️'],
        'delivery' => ['type' => 'delivery', 'x' => 8, 'y' => 3, 'label' => 'Delivery', 'icon' => '📦'],
        'trash' => ['type' => 'trash', 'x' => 5, 'y' => 5, 'label' => 'Sampah', 'icon' => '🗑️'],
        'fridge' => ['type' => 'fridge', 'x' => 8, 'y' => 5, 'label' => 'Kulkas', 'icon' => '🧊'],
    ];

    public function state(KoKiProgress $progress): array
    {
        $stored = $progress->ipo_state ?? [];
        $defaults = [
            'adventure_version' => self::ADVENTURE_VERSION,
            'intro_seen' => false,
            'unlocked_levels' => [1],
            'approved_levels' => [1],
            'completed_levels' => [],
            'current_level' => 1,
            'level_progress' => [],
            'score' => 0,
            'best_combo' => 0,
            'order_count' => 0,
            'training_replay_seen' => false,
            'factory_finished' => false,
            'final_recipe' => null,
            'exit_challenges' => ['identify' => false, 'arrange' => false],
            'badge' => null,
            'completed' => false,
            'legacy_state' => null,
        ];

        if (($stored['adventure_version'] ?? null) !== self::ADVENTURE_VERSION) {
            $defaults['intro_seen'] = (bool) ($stored['intro_seen'] ?? false);
            $defaults['legacy_state'] = $stored === [] ? null : $stored;

            return $defaults;
        }

        $state = [...$defaults, ...$stored];
        if (($stored['completed'] ?? false) && ! array_key_exists('factory_finished', $stored)) {
            $state['factory_finished'] = true;
            $state['completed'] = false;
            $state['final_recipe'] = 'apple_juice';
        }
        $approvedLevels = array_map('intval', $state['approved_levels'] ?? [1]);
        $approvedLevels[] = 1;
        array_push($approvedLevels, ...array_map('intval', $state['completed_levels'] ?? []));
        foreach ($state['level_progress'] ?? [] as $level => $levelProgress) {
            if (($levelProgress['runtime']['active'] ?? false) === true) {
                $approvedLevels[] = (int) $level;
            }
        }
        $approvedLevels = array_values(array_unique(array_filter(
            $approvedLevels,
            fn (int $level): bool => isset(self::LEVELS[$level]),
        )));
        sort($approvedLevels);
        $state['approved_levels'] = $approvedLevels;
        foreach (array_keys(self::LEVELS) as $level) {
            $state['level_progress'][$level] = [
                ...$this->emptyLevelProgress(),
                ...($state['level_progress'][$level] ?? []),
            ];
        }

        return $state;
    }

    public function configuration(): array
    {
        return [
            'grid' => ['width' => self::GRID_WIDTH, 'height' => self::GRID_HEIGHT],
            'levels' => array_map(
                fn (array $level, int $number): array => ['number' => $number, ...$level],
                self::LEVELS,
                array_keys(self::LEVELS),
            ),
            'stations' => array_map(
                fn (array $station, string $id): array => ['id' => $id, ...$station],
                self::STATIONS,
                array_keys(self::STATIONS),
            ),
            'ingredients' => self::INGREDIENTS,
            'products' => self::PRODUCTS,
            'recipes' => self::RECIPES,
            'powerups' => self::POWER_UPS,
            'fridge_slots' => 2,
            'estimated_minutes' => array_sum(array_column(self::LEVELS, 'minutes')),
        ];
    }

    public function isUnlocked(array $state, int $level): bool
    {
        if ($level > 1 && in_array(1, $state['completed_levels'], true) && ! $state['training_replay_seen']) {
            return false;
        }

        return in_array($level, $state['unlocked_levels'], true)
            && in_array($level, $state['approved_levels'], true);
    }

    /** @return list<int> */
    public function approvedLevels(KoKiProgress $progress): array
    {
        return $this->state($progress)['approved_levels'];
    }

    public function nextApprovableLevel(KoKiProgress $progress): ?int
    {
        $state = $this->state($progress);

        foreach (array_keys(self::LEVELS) as $level) {
            if ($level === 1 || in_array($level, $state['approved_levels'], true)) {
                continue;
            }

            if (in_array($level, $state['unlocked_levels'], true)
                && in_array($level - 1, $state['completed_levels'], true)) {
                return $level;
            }
        }

        return null;
    }

    public function approveNextLevel(KoKiProgress $progress, int $targetLevel): ?KoKiProgress
    {
        if ($this->nextApprovableLevel($progress) !== $targetLevel) {
            return null;
        }

        $state = $this->state($progress);
        $state['approved_levels'][] = $targetLevel;
        $state['approved_levels'] = array_values(array_unique(array_map('intval', $state['approved_levels'])));
        sort($state['approved_levels']);

        return $this->save($progress, $state, $progress->last_phase);
    }

    public function revokeLatestLevel(KoKiProgress $progress): ?KoKiProgress
    {
        $state = $this->state($progress);
        $revocable = array_values(array_filter(
            $state['approved_levels'],
            fn (int $level): bool => $level > 1 && ! in_array($level, $state['completed_levels'], true),
        ));
        if ($revocable === []) {
            return null;
        }

        $latestLevel = max($revocable);
        $state['approved_levels'] = array_values(array_filter(
            $state['approved_levels'],
            fn (int $level): bool => $level !== $latestLevel,
        ));

        return $this->save($progress, $state, $progress->last_phase);
    }

    public function completeIntro(KoKiProgress $progress): KoKiProgress
    {
        $state = $this->state($progress);
        $state['intro_seen'] = true;

        return $this->save($progress, $state, 'factory_map');
    }

    public function startLevel(KoKiProgress $progress, int $level): KoKiProgress
    {
        $state = $this->state($progress);
        if (! $state['intro_seen'] || ! isset(self::LEVELS[$level]) || ! $this->isUnlocked($state, $level)) {
            throw ValidationException::withMessages(['level' => 'Level ini masih terkunci.']);
        }

        $levelProgress = $state['level_progress'][$level] ?? $this->emptyLevelProgress();
        $levelProgress['attempts']++;
        $definition = self::LEVELS[$level];
        $levelProgress['runtime'] = [
            'active' => true,
            'player' => ['x' => 4, 'y' => 3, 'direction' => 'south'],
            'inventory' => [],
            'blender' => ['ingredients' => [], 'status' => 'empty', 'result' => null],
            'fridge' => ['slots' => [null, null]],
            'active_orders' => $level === 1
                ? ['apple_juice']
                : $this->initialOrders($progress, $level, $definition['orders'], $levelProgress['attempts']),
            'score' => 0,
            'combo' => 0,
            'best_combo' => 0,
            'orders_completed' => 0,
            'wrong_recipes' => 0,
            'discarded_items' => 0,
            'started_at' => now()->toIso8601String(),
            'ends_at' => $definition['timer'] === null ? null : now()->addSeconds($definition['timer'])->toIso8601String(),
            'effects' => [],
            'paused' => false,
            'paused_at' => null,
            'recipe_history' => [],
            'powerup_index' => 0,
            'powerup' => $level === 5 ? $this->powerup(0) : null,
        ];
        if (in_array('double_order', $definition['systems'], true)) {
            $levelProgress['runtime']['batch_count'] = 0;
            $levelProgress['runtime']['batch_errors'] = 0;
        }
        $state['level_progress'][$level] = $levelProgress;
        $state['current_level'] = $level;

        return $this->save($progress, $state, 'factory_level');
    }

    public function acknowledgeTrainingReplay(KoKiProgress $progress): KoKiProgress
    {
        $state = $this->state($progress);
        if (! in_array(1, $state['completed_levels'], true)) {
            throw ValidationException::withMessages(['action' => 'Selesaikan Training terlebih dahulu.']);
        }

        $state['training_replay_seen'] = true;
        $state['unlocked_levels'][] = 2;
        $state['unlocked_levels'] = array_values(array_unique(array_map('intval', $state['unlocked_levels'])));
        sort($state['unlocked_levels']);

        return $this->save($progress, $state, 'factory_map');
    }

    /** @return array{progress: KoKiProgress, result: array<string, mixed>} */
    public function submitExitChallenge(KoKiProgress $progress, array $data): array
    {
        $state = $this->state($progress);
        if (! $state['factory_finished']) {
            throw ValidationException::withMessages(['action' => 'Selesaikan Factory Frenzy terlebih dahulu.']);
        }

        if ($data['action'] === 'factory_exit_identify') {
            $correct = $data['answer'] === 'space';
            if ($correct) {
                $state['exit_challenges']['identify'] = true;
            }
        } else {
            $correct = $data['card_order'] === ['input', 'process', 'output'];
            if ($correct) {
                $state['exit_challenges']['arrange'] = true;
            }
        }

        if ($state['exit_challenges']['identify'] && $state['exit_challenges']['arrange']) {
            $state['completed'] = true;
            $state['badge'] = 'Factory Master';
        }

        return [
            'progress' => $this->save($progress, $state, $state['completed'] ? 'completed' : 'factory_finale'),
            'result' => ['correct' => $correct, 'completed' => $state['completed'], 'badge' => $state['badge']],
        ];
    }

    /** @return array{progress: KoKiProgress, result: array<string, mixed>} */
    public function play(KoKiProgress $progress, array $data): array
    {
        $state = $this->state($progress);
        $level = (int) $data['level'];
        if (! $this->isUnlocked($state, $level)) {
            throw ValidationException::withMessages(['level' => 'Level ini masih terkunci.']);
        }

        $levelProgress = $state['level_progress'][$level] ?? $this->emptyLevelProgress();
        $runtime = $levelProgress['runtime'] ?? null;
        if (! is_array($runtime) || ! ($runtime['active'] ?? false)) {
            throw ValidationException::withMessages(['action' => 'Tekan Mulai Level terlebih dahulu.']);
        }
        $runtime['fridge'] ??= ['slots' => [null, null]];
        if (in_array('double_order', self::LEVELS[$level]['systems'], true)) {
            $runtime['batch_count'] ??= intdiv((int) ($runtime['orders_completed'] ?? 0), 2);
            $runtime['batch_errors'] ??= 0;
        }

        if ($this->timerExpired($level, $runtime)) {
            $runtime['active'] = false;
            $levelProgress['runtime'] = $runtime;
            $state['level_progress'][$level] = $levelProgress;

            return [
                'progress' => $this->save($progress, $state, 'factory_level'),
                'result' => ['time_expired' => true, 'level_completed' => false],
            ];
        }

        $runtime = $this->applySnapshot($runtime, $data, $level);
        $result = match ($data['action']) {
            'factory_sync' => ['saved' => true],
            'factory_blend' => $this->blend($runtime, $data, $level),
            'factory_deliver' => $this->deliver($progress, $state, $levelProgress, $runtime, $level, $data),
            'factory_double_deliver' => $this->doubleDeliver($progress, $state, $levelProgress, $runtime, $level),
            'factory_fridge_store' => $this->storeInFridge($runtime, $level, $data),
            'factory_fridge_take' => $this->takeFromFridge($runtime, $level, $data),
            'factory_discard' => $this->discard($runtime, $data, $level),
            'factory_powerup' => $this->activatePowerup($runtime, $level, $data),
            'factory_pause' => $this->pause($runtime),
            'factory_resume' => $this->resume($runtime),
            default => throw ValidationException::withMessages(['action' => 'Aksi Factory Rush tidak dikenal.']),
        };

        $levelProgress['runtime'] = $runtime;
        $this->updatePersonalBest($levelProgress, $runtime);
        $state['level_progress'][$level] = $levelProgress;
        $state['current_level'] = $level;
        $this->updateTotals($state);

        return [
            'progress' => $this->save($progress, $state, ($result['level_completed'] ?? false) ? 'factory_map' : 'factory_level'),
            'result' => $result,
        ];
    }

    public function recipeResult(array $ingredients): string
    {
        $ingredients = array_values($ingredients);
        sort($ingredients);

        foreach (self::RECIPES as $product => $recipe) {
            sort($recipe);
            if ($ingredients === $recipe) {
                return $product;
            }
        }

        return 'mystery';
    }

    private function applySnapshot(array $runtime, array $data, int $level): array
    {
        if (isset($data['player']) && $this->isWalkable((int) $data['player']['x'], (int) $data['player']['y'], $level)) {
            $runtime['player'] = [
                'x' => (int) $data['player']['x'],
                'y' => (int) $data['player']['y'],
                'direction' => $data['player']['direction'],
            ];
        }

        if (isset($data['inventory'])) {
            $runtime['inventory'] = array_slice(array_values($data['inventory']), 0, 2);
        }

        if (isset($data['blender'])) {
            $runtime['blender'] = [
                'ingredients' => array_values($data['blender']['ingredients'] ?? []),
                'status' => $data['blender']['status'],
                'result' => $data['blender']['result'] ?? null,
            ];
        }

        return $runtime;
    }

    private function blend(array &$runtime, array $data, int $level): array
    {
        $ingredients = array_values($data['ingredients']);
        if ($ingredients === []) {
            throw ValidationException::withMessages(['ingredients' => 'Masukkan bahan ke blender terlebih dahulu.']);
        }

        $output = $this->recipeResult($ingredients);
        $runtime['blender'] = ['ingredients' => [], 'status' => 'ready', 'result' => $output];
        if ($output === 'mystery') {
            $runtime['wrong_recipes']++;
            $runtime['combo'] = 0;
            if (in_array('double_order', self::LEVELS[$level]['systems'], true)) {
                $runtime['batch_errors']++;
            }
        }

        return [
            'output' => $output,
            'output_label' => self::PRODUCTS[$output]['label'],
            'output_icon' => self::PRODUCTS[$output]['icon'],
            'level_completed' => false,
        ];
    }

    private function deliver(
        KoKiProgress $progress,
        array &$state,
        array &$levelProgress,
        array &$runtime,
        int $level,
        array $data,
    ): array {
        if (in_array('double_order', self::LEVELS[$level]['systems'], true)) {
            $runtime['batch_errors']++;

            return [
                'correct' => false,
                'double_order_incomplete' => true,
                'message' => 'Pesanannya harus dikirim bersama!',
                'score' => $runtime['score'],
                'combo' => $runtime['combo'],
                'level_completed' => false,
            ];
        }

        $item = $data['item'];
        $slot = array_search($item, $runtime['active_orders'], true);
        $inventorySlot = array_search($item, $runtime['inventory'], true);
        $correct = $slot !== false && $inventorySlot !== false;

        if (! $correct) {
            $runtime['combo'] = 0;

            return ['correct' => false, 'score' => $runtime['score'], 'combo' => 0, 'level_completed' => false];
        }

        array_splice($runtime['inventory'], $inventorySlot, 1);
        $runtime['combo']++;
        $runtime['best_combo'] = max($runtime['best_combo'], $runtime['combo']);
        $comboBonus = min(4, max(0, $runtime['combo'] - 1)) * 20;
        $points = 100 + $comboBonus;
        $runtime['score'] += $points;
        $runtime['orders_completed']++;
        $runtime['recipe_history'][] = $item;
        $target = self::LEVELS[$level]['target'];
        $levelCompleted = $runtime['orders_completed'] >= $target;

        if ($levelCompleted) {
            $runtime['active'] = false;
            $levelProgress['completed'] = true;
            $state['completed_levels'][] = $level;
            $state['completed_levels'] = array_values(array_unique(array_map('intval', $state['completed_levels'])));
            sort($state['completed_levels']);
            if ($level !== 1 && isset(self::LEVELS[$level + 1])) {
                $state['unlocked_levels'][] = $level + 1;
                $state['unlocked_levels'] = array_values(array_unique(array_map('intval', $state['unlocked_levels'])));
                sort($state['unlocked_levels']);
            }
            if (count($state['completed_levels']) === self::MISSION_COUNT) {
                $state['factory_finished'] = true;
                $state['final_recipe'] = $item;
            }
        } else {
            $runtime['active_orders'][$slot] = $this->nextOrder($progress, $level, $runtime, (int) $slot);
        }

        return [
            'correct' => true,
            'points' => $points,
            'combo_bonus' => $comboBonus,
            'score' => $runtime['score'],
            'combo' => $runtime['combo'],
            'orders_completed' => $runtime['orders_completed'],
            'active_orders' => $runtime['active_orders'],
            'level_completed' => $levelCompleted,
        ];
    }

    private function storeInFridge(array &$runtime, int $level, array $data): array
    {
        $this->requireDoubleOrderLevel($level);
        $item = $data['item'];
        $inventorySlot = array_search($item, $runtime['inventory'], true);
        if ($inventorySlot === false || ! in_array($item, $runtime['active_orders'], true)) {
            throw ValidationException::withMessages(['item' => 'Kulkas hanya menerima jus untuk order yang aktif.']);
        }
        if (in_array($item, $runtime['fridge']['slots'], true)) {
            throw ValidationException::withMessages(['item' => 'Jus untuk order ini sudah tersimpan.']);
        }

        $fridgeSlot = array_search(null, $runtime['fridge']['slots'], true);
        if ($fridgeSlot === false) {
            throw ValidationException::withMessages(['item' => 'Kulkas sudah penuh.']);
        }

        array_splice($runtime['inventory'], $inventorySlot, 1);
        $runtime['fridge']['slots'][$fridgeSlot] = $item;

        return [
            'stored' => $item,
            'slot' => $fridgeSlot,
            'order_statuses' => $this->orderStatuses($runtime),
            'double_order_ready' => $this->doubleOrderReady($runtime),
            'level_completed' => false,
        ];
    }

    private function takeFromFridge(array &$runtime, int $level, array $data): array
    {
        $this->requireDoubleOrderLevel($level);
        $slot = (int) $data['fridge_slot'];
        $item = $runtime['fridge']['slots'][$slot] ?? null;
        if ($item === null) {
            throw ValidationException::withMessages(['fridge_slot' => 'Slot kulkas ini kosong.']);
        }
        if (count($runtime['inventory']) >= 2) {
            throw ValidationException::withMessages(['fridge_slot' => 'Tas Ko-Ki sudah penuh.']);
        }

        $runtime['inventory'][] = $item;
        $runtime['fridge']['slots'][$slot] = null;

        return [
            'taken' => $item,
            'slot' => $slot,
            'order_statuses' => $this->orderStatuses($runtime),
            'double_order_ready' => $this->doubleOrderReady($runtime),
            'level_completed' => false,
        ];
    }

    private function doubleDeliver(
        KoKiProgress $progress,
        array &$state,
        array &$levelProgress,
        array &$runtime,
        int $level,
    ): array {
        $this->requireDoubleOrderLevel($level);
        if (! $this->doubleOrderReady($runtime)) {
            $runtime['batch_errors']++;

            return [
                'correct' => false,
                'double_order_incomplete' => true,
                'message' => 'Pesanannya harus dikirim bersama!',
                'order_statuses' => $this->orderStatuses($runtime),
                'level_completed' => false,
            ];
        }

        $delivered = $runtime['active_orders'];
        $runtime['inventory'] = [];
        $runtime['combo']++;
        $runtime['best_combo'] = max($runtime['best_combo'], $runtime['combo']);
        $comboBonus = $level === 5 ? min(4, max(0, $runtime['combo'] - 1)) * 40 : 0;
        $orderPoints = count($delivered) * 100;
        $doubleDeliveryBonus = 100;
        $deliveryPoints = $orderPoints + $doubleDeliveryBonus;
        $perfectBonus = ($runtime['batch_errors'] ?? 0) === 0 ? 100 : 0;
        $points = $deliveryPoints + $perfectBonus + $comboBonus;
        $runtime['score'] += $points;
        $runtime['orders_completed'] += 2;
        $runtime['batch_count']++;
        $runtime['batch_errors'] = 0;
        array_push($runtime['recipe_history'], ...$delivered);
        $definition = self::LEVELS[$level];
        $levelCompleted = $runtime['score'] >= $definition['target_score']
            && $runtime['batch_count'] >= $definition['minimum_batches'];

        if ($levelCompleted) {
            $this->completeLevel($state, $levelProgress, $runtime, $level, $delivered[array_key_last($delivered)]);
        } else {
            $runtime['active_orders'] = $this->initialOrders(
                $progress,
                $level,
                2,
                $runtime['orders_completed'] + $levelProgress['attempts'],
            );
        }

        return [
            'correct' => true,
            'double_delivery' => true,
            'points' => $points,
            'order_points' => $orderPoints,
            'double_delivery_bonus' => $doubleDeliveryBonus,
            'delivery_points' => $deliveryPoints,
            'perfect_bonus' => $perfectBonus,
            'combo_bonus' => $comboBonus,
            'combo' => $runtime['combo'],
            'score' => $runtime['score'],
            'orders_completed' => $runtime['orders_completed'],
            'batch_count' => $runtime['batch_count'],
            'target_score' => $definition['target_score'],
            'minimum_batches' => $definition['minimum_batches'],
            'active_orders' => $runtime['active_orders'],
            'order_statuses' => $this->orderStatuses($runtime),
            'level_completed' => $levelCompleted,
        ];
    }

    private function completeLevel(
        array &$state,
        array &$levelProgress,
        array &$runtime,
        int $level,
        string $lastItem,
    ): void {
        $runtime['active'] = false;
        $levelProgress['completed'] = true;
        $state['completed_levels'][] = $level;
        $state['completed_levels'] = array_values(array_unique(array_map('intval', $state['completed_levels'])));
        sort($state['completed_levels']);
        if ($level !== 1 && isset(self::LEVELS[$level + 1])) {
            $state['unlocked_levels'][] = $level + 1;
            $state['unlocked_levels'] = array_values(array_unique(array_map('intval', $state['unlocked_levels'])));
            sort($state['unlocked_levels']);
        }
        if (count($state['completed_levels']) === self::MISSION_COUNT) {
            $state['factory_finished'] = true;
            $state['final_recipe'] = $lastItem;
        }
    }

    private function doubleOrderReady(array $runtime): bool
    {
        $orders = $runtime['active_orders'];
        $inventory = $runtime['inventory'];
        sort($orders);
        sort($inventory);

        return $orders === $inventory
            && ! array_filter($runtime['fridge']['slots'], fn (?string $item): bool => $item !== null);
    }

    private function orderStatuses(array $runtime): array
    {
        return array_map(function (string $order) use ($runtime): string {
            if (in_array($order, $runtime['fridge']['slots'], true)) {
                return 'stored';
            }
            if (in_array($order, $runtime['inventory'], true)) {
                return 'ready';
            }

            return 'making';
        }, $runtime['active_orders']);
    }

    private function requireDoubleOrderLevel(int $level): void
    {
        if (! in_array('double_order', self::LEVELS[$level]['systems'], true)) {
            throw ValidationException::withMessages(['level' => 'Kulkas hanya digunakan pada Double Order.']);
        }
    }

    private function discard(array &$runtime, array $data, int $level): array
    {
        $slot = array_search($data['item'], $runtime['inventory'], true);
        if ($slot === false) {
            throw ValidationException::withMessages(['item' => 'Benda itu tidak sedang dibawa Ko-Ki.']);
        }

        array_splice($runtime['inventory'], $slot, 1);
        $runtime['discarded_items']++;
        $runtime['combo'] = 0;
        if (in_array('double_order', self::LEVELS[$level]['systems'], true)) {
            $runtime['batch_errors']++;
        }

        return ['discarded' => $data['item'], 'level_completed' => false];
    }

    private function activatePowerup(array &$runtime, int $level, array $data): array
    {
        if ($level !== 5 || ($runtime['powerup']['type'] ?? null) !== $data['powerup']) {
            throw ValidationException::withMessages(['powerup' => 'Power-up ini belum tersedia.']);
        }

        $type = $data['powerup'];
        $definition = self::POWER_UPS[$type];
        if ($type === 'time_bonus' && $runtime['ends_at'] !== null) {
            $runtime['ends_at'] = CarbonImmutable::parse($runtime['ends_at'])->addSeconds($definition['seconds'])->toIso8601String();
        } else {
            $runtime['effects'][$type] = now()->addSeconds($definition['seconds'])->toIso8601String();
        }

        $runtime['powerup_index']++;
        $runtime['powerup'] = $this->powerup($runtime['powerup_index'], 15);

        return [
            'powerup' => $type,
            'label' => $definition['label'],
            'seconds' => $definition['seconds'],
            'level_completed' => false,
        ];
    }

    private function pause(array &$runtime): array
    {
        if (! ($runtime['paused'] ?? false)) {
            $runtime['paused'] = true;
            $runtime['paused_at'] = now()->toIso8601String();
        }

        return ['paused' => true, 'level_completed' => false];
    }

    private function resume(array &$runtime): array
    {
        if ($runtime['paused'] ?? false) {
            $pausedAt = CarbonImmutable::parse($runtime['paused_at']);
            $pausedSeconds = max(0, (int) $pausedAt->diffInSeconds(now()));
            if ($runtime['ends_at'] !== null) {
                $runtime['ends_at'] = CarbonImmutable::parse($runtime['ends_at'])->addSeconds($pausedSeconds)->toIso8601String();
            }
            foreach ($runtime['effects'] as $effect => $expiresAt) {
                $runtime['effects'][$effect] = CarbonImmutable::parse($expiresAt)->addSeconds($pausedSeconds)->toIso8601String();
            }
            $runtime['paused'] = false;
            $runtime['paused_at'] = null;
        }

        return ['paused' => false, 'level_completed' => false];
    }

    private function timerExpired(int $level, array $runtime): bool
    {
        return self::LEVELS[$level]['timer'] !== null
            && ! ($runtime['paused'] ?? false)
            && $runtime['ends_at'] !== null
            && now()->greaterThanOrEqualTo(CarbonImmutable::parse($runtime['ends_at']));
    }

    private function initialOrders(KoKiProgress $progress, int $level, int $count, int $attempt): array
    {
        $orders = [];
        for ($slot = 0; $slot < $count; $slot++) {
            $orders[] = $this->orderAt($progress, $level, $attempt + $slot);
        }

        if ($count === 2 && $orders[0] === $orders[1]) {
            $products = array_keys(self::RECIPES);
            $orders[1] = $products[(array_search($orders[0], $products, true) + 1) % count($products)];
        }

        return $orders;
    }

    private function nextOrder(KoKiProgress $progress, int $level, array $runtime, int $slot): string
    {
        $order = $this->orderAt($progress, $level, $runtime['orders_completed'] + $slot + 1);
        $otherOrders = $runtime['active_orders'];
        unset($otherOrders[$slot]);
        if (in_array($order, $otherOrders, true)) {
            $products = array_keys(self::RECIPES);
            $order = $products[(array_search($order, $products, true) + 1) % count($products)];
        }

        return $order;
    }

    private function orderAt(KoKiProgress $progress, int $level, int $offset): string
    {
        $seed = hexdec(substr(hash('sha256', $progress->getKey().'-'.$level), 0, 6));

        return self::ORDER_SEQUENCE[($seed + $offset) % count(self::ORDER_SEQUENCE)];
    }

    private function powerup(int $index, int $delaySeconds = 0): array
    {
        $types = array_keys(self::POWER_UPS);
        $positions = [[7, 5], [3, 5], [7, 1]];
        [$x, $y] = $positions[$index % count($positions)];

        return [
            'type' => $types[$index % count($types)],
            'x' => $x,
            'y' => $y,
            'available_at' => now()->addSeconds($delaySeconds)->toIso8601String(),
        ];
    }

    private function updatePersonalBest(array &$levelProgress, array $runtime): void
    {
        $levelProgress['best_score'] = max((int) $levelProgress['best_score'], (int) $runtime['score']);
        $levelProgress['best_combo'] = max((int) $levelProgress['best_combo'], (int) $runtime['best_combo']);
        $levelProgress['best_orders'] = max((int) $levelProgress['best_orders'], (int) $runtime['orders_completed']);
    }

    private function updateTotals(array &$state): void
    {
        $state['score'] = array_sum(array_column($state['level_progress'], 'best_score'));
        $state['best_combo'] = max([0, ...array_column($state['level_progress'], 'best_combo')]);
        $state['order_count'] = array_sum(array_column($state['level_progress'], 'best_orders'));
    }

    private function isWalkable(int $x, int $y, int $level): bool
    {
        if ($x < 0 || $x >= self::GRID_WIDTH || $y < 0 || $y >= self::GRID_HEIGHT) {
            return false;
        }

        if (in_array('conveyor', self::LEVELS[$level]['systems'], true) && $y === 0 && $x >= 2 && $x <= 7) {
            return false;
        }

        foreach (self::STATIONS as $station) {
            if ($station['type'] === 'fridge' && $level < 4) {
                continue;
            }
            if ($station['type'] === 'ingredient' && in_array('conveyor', self::LEVELS[$level]['systems'], true)) {
                continue;
            }
            if ($station['x'] === $x && $station['y'] === $y) {
                return false;
            }
        }

        return true;
    }

    private function save(KoKiProgress $progress, array $state, string $phase): KoKiProgress
    {
        $progress->forceFill([
            'last_phase' => $phase,
            'current_level' => $state['current_level'],
            'completed_levels' => $state['completed_levels'],
            'attempt_count' => $state['order_count'],
            'ipo_state' => $state,
            'completed_at' => $state['completed'] ? ($progress->completed_at ?? now()) : null,
        ])->save();

        return $progress->refresh();
    }

    private function emptyLevelProgress(): array
    {
        return [
            'completed' => false,
            'attempts' => 0,
            'best_score' => 0,
            'best_combo' => 0,
            'best_orders' => 0,
            'runtime' => null,
        ];
    }
}
