<?php

namespace Tests\Feature;

use App\Models\KoKiProgress;
use App\Services\KoKiAdventureService;
use App\Services\KoKiProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WanKoKiMeetingTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_intro_opens_a_five_level_factory_rush_map(): void
    {
        $this->get(route('tools.ko-ki.index'))->assertOk()->assertSee('Factory Rush');
        $this->startMeetingTwo();
        $this->get(route('tools.ko-ki.meeting-two.activity'))->assertOk()->assertSee('Mulai Shift');
        $this->get(route('tools.ko-ki.meeting-two.map'))
            ->assertRedirect(route('tools.ko-ki.meeting-two.activity'));

        $this->completeIntro();
        $this->get(route('tools.ko-ki.meeting-two.map'))
            ->assertOk()
            ->assertSee('Training')
            ->assertSee('Juice Rush')
            ->assertSee('Conveyor Chaos')
            ->assertSee('Double Order')
            ->assertSee('Factory Frenzy')
            ->assertSee('sekitar 40 menit');
    }

    public function test_meeting_two_accepts_only_grades_four_and_five_and_uses_a_separate_session(): void
    {
        foreach ([1, 2, 3, 6] as $grade) {
            $this->post(route('tools.ko-ki.meeting-two.start'), ['student_name' => 'Naya', 'grade' => $grade])
                ->assertSessionHasErrors('grade');
        }

        $this->post(route('tools.ko-ki.start'), ['student_name' => 'P1', 'grade' => 4]);
        $meetingOneId = session(KoKiProgressService::SESSION_KEY);
        $this->post(route('tools.ko-ki.meeting-two.start'), ['student_name' => 'P2', 'grade' => 5]);

        $this->assertSame($meetingOneId, session(KoKiProgressService::SESSION_KEY));
        $this->assertNotSame($meetingOneId, session(KoKiProgressService::MEETING_TWO_SESSION_KEY));
    }

    public function test_levels_are_unlocked_in_order(): void
    {
        $progress = $this->startFactory();

        $this->get(route('tools.ko-ki.meeting-two.mission', 'level-2'))
            ->assertRedirect(route('tools.ko-ki.meeting-two.map'));
        $this->startLevel(2)->assertUnprocessable()->assertJsonValidationErrors('level');

        $this->startLevel(1)->assertOk();
        $this->deliverCurrentOrder(1)->assertOk();
        $this->assertSame([1], $progress->refresh()->ipo_state['unlocked_levels']);
        $this->get(route('tools.ko-ki.meeting-two.map'))
            ->assertRedirect(route('tools.ko-ki.meeting-two.mission', 'level-1'));
        $this->patchJson(route('tools.ko-ki.meeting-two.update'), ['action' => 'factory_training_replay'])
            ->assertOk()->assertJsonPath('result.training_replay_seen', true);
        $this->assertSame([1, 2], $progress->refresh()->ipo_state['unlocked_levels']);
        $this->assertSame([1], $progress->refresh()->ipo_state['approved_levels']);
        $this->get(route('tools.ko-ki.meeting-two.mission', 'level-2'))
            ->assertRedirect(route('tools.ko-ki.meeting-two.map'));
        $this->startLevel(2)->assertUnprocessable()->assertJsonValidationErrors('level');
        $this->assertNull(app(KoKiAdventureService::class)->approveNextLevel($progress, 3));
        $this->assertNotNull(app(KoKiAdventureService::class)->approveNextLevel($progress, 2));
        $this->assertSame([1, 2], $progress->refresh()->ipo_state['approved_levels']);
        $this->get(route('tools.ko-ki.meeting-two.mission', 'level-2'))->assertOk()->assertSee('waktu empat menit');
    }

    public function test_training_has_no_timer_and_requires_one_apple_juice(): void
    {
        $progress = $this->startFactory();
        $this->startLevel(1)->assertOk()->assertJsonPath('result.level_started', true);

        $runtime = $progress->refresh()->ipo_state['level_progress'][1]['runtime'];
        $this->assertNull($runtime['ends_at']);
        $this->assertSame(['apple_juice'], $runtime['active_orders']);

        $this->deliverCurrentOrder(1)->assertOk()->assertJsonPath('result.level_completed', true);
        $state = $progress->refresh()->ipo_state;
        $this->assertSame([1], $state['completed_levels']);
        $this->assertSame(100, $state['level_progress'][1]['best_score']);
        $this->assertFalse($state['training_replay_seen']);
        $this->get(route('tools.ko-ki.meeting-two.mission', 'level-1'))
            ->assertOk()
            ->assertSee('Replay Training')
            ->assertSee('INPUT')
            ->assertSee('Blender mencampur bahan')
            ->assertSee('OUTPUT');
    }

    public function test_juice_rush_has_four_minute_timer_random_orders_and_combo(): void
    {
        $progress = $this->progressAt(2);
        Carbon::setTestNow('2026-08-12 10:00:00');
        $this->startLevel(2)->assertOk();

        $runtime = $progress->refresh()->ipo_state['level_progress'][2]['runtime'];
        $this->assertEquals(240, Carbon::parse($runtime['started_at'])->diffInSeconds(Carbon::parse($runtime['ends_at'])));
        $this->assertContains($runtime['active_orders'][0], array_keys(KoKiAdventureService::RECIPES));

        $this->deliverCurrentOrder(2)->assertJsonPath('result.combo', 1)->assertJsonPath('result.points', 100);
        $this->deliverCurrentOrder(2)->assertJsonPath('result.combo', 2)->assertJsonPath('result.points', 120);
        $runtime = $progress->refresh()->ipo_state['level_progress'][2]['runtime'];
        $wrong = collect(array_keys(KoKiAdventureService::RECIPES))->first(fn (string $item): bool => ! in_array($item, $runtime['active_orders'], true));
        $this->factoryAction(2, 'factory_deliver', ['inventory' => [$wrong], 'item' => $wrong])
            ->assertJsonPath('result.correct', false)->assertJsonPath('result.combo', 0);
        Carbon::setTestNow();
    }

    public function test_timer_expiry_keeps_personal_best_and_allows_replay(): void
    {
        $progress = $this->progressAt(2);
        Carbon::setTestNow('2026-08-12 10:00:00');
        $this->startLevel(2)->assertOk();
        $this->deliverCurrentOrder(2)->assertOk();

        Carbon::setTestNow('2026-08-12 10:04:01');
        $this->factoryAction(2, 'factory_sync')->assertOk()->assertJsonPath('result.time_expired', true);
        $levelProgress = $progress->refresh()->ipo_state['level_progress'][2];
        $this->assertFalse($levelProgress['runtime']['active']);
        $this->assertSame(100, $levelProgress['best_score']);

        $this->startLevel(2)->assertOk();
        $levelProgress = $progress->refresh()->ipo_state['level_progress'][2];
        $this->assertTrue($levelProgress['runtime']['active']);
        $this->assertSame(100, $levelProgress['best_score']);
        $this->assertSame(2, $levelProgress['attempts']);
        Carbon::setTestNow();
    }

    public function test_pause_stops_and_then_extends_the_server_timer(): void
    {
        $progress = $this->progressAt(2);
        Carbon::setTestNow('2026-08-12 10:00:00');
        $this->startLevel(2)->assertOk();
        $endsAt = $progress->refresh()->ipo_state['level_progress'][2]['runtime']['ends_at'];

        Carbon::setTestNow('2026-08-12 10:01:00');
        $this->factoryAction(2, 'factory_pause')->assertOk()->assertJsonPath('result.paused', true);
        Carbon::setTestNow('2026-08-12 10:03:00');
        $this->factoryAction(2, 'factory_resume')->assertOk()->assertJsonPath('result.paused', false);

        $runtime = $progress->refresh()->ipo_state['level_progress'][2]['runtime'];
        $this->assertFalse($runtime['paused']);
        $this->assertEquals(120, Carbon::parse($endsAt)->diffInSeconds(Carbon::parse($runtime['ends_at'])));
        Carbon::setTestNow();
    }

    public function test_conveyor_chaos_has_conveyor_and_harmless_cleaner(): void
    {
        $this->progressAt(3);

        $this->get(route('tools.ko-ki.meeting-two.mission', 'level-3'))
            ->assertOk()
            ->assertSee('data-conveyor-item', false)
            ->assertSee('data-cleaner', false)
            ->assertDontSee('data-station="apple"', false)
            ->assertDontSee('data-station="fridge"', false);
    }

    public function test_double_order_requires_fridge_and_joint_delivery(): void
    {
        $progress = $this->progressAt(4);
        $this->get(route('tools.ko-ki.meeting-two.mission', 'level-4'))
            ->assertOk()
            ->assertSee('data-station="fridge"', false)
            ->assertSee('2 slot')
            ->assertSee('DOUBLE ORDER READY!');
        $this->startLevel(4)->assertOk();
        $orders = $progress->refresh()->ipo_state['level_progress'][4]['runtime']['active_orders'];
        $this->assertCount(2, $orders);
        $this->assertNotSame($orders[0], $orders[1]);

        $this->factoryAction(4, 'factory_deliver', ['inventory' => [$orders[1]], 'item' => $orders[1]])
            ->assertOk()
            ->assertJsonPath('result.correct', false)
            ->assertJsonPath('result.message', 'Pesanannya harus dikirim bersama!');

        $this->factoryAction(4, 'factory_fridge_store', ['inventory' => [$orders[1]], 'item' => $orders[1]])
            ->assertOk()
            ->assertJsonPath('result.slot', 0)
            ->assertJsonPath('result.order_statuses.1', 'stored');
        $runtime = $progress->refresh()->ipo_state['level_progress'][4]['runtime'];
        $this->assertSame([$orders[1], null], $runtime['fridge']['slots']);
        $this->assertSame($orders, $runtime['active_orders']);
        $this->factoryAction(4, 'factory_fridge_store', ['inventory' => [$orders[1]], 'item' => $orders[1]])
            ->assertUnprocessable()->assertJsonValidationErrors('item');

        $this->factoryAction(4, 'factory_double_deliver', ['inventory' => [$orders[0]]])
            ->assertOk()->assertJsonPath('result.correct', false);
        $this->factoryAction(4, 'factory_fridge_take', ['inventory' => [$orders[0]], 'fridge_slot' => 0])
            ->assertOk()
            ->assertJsonPath('result.double_order_ready', true)
            ->assertJsonPath('factory_state.level_progress.4.runtime.inventory.1', $orders[1]);
        $this->factoryAction(4, 'factory_double_deliver')
            ->assertOk()
            ->assertJsonPath('result.correct', true)
            ->assertJsonPath('result.double_delivery', true)
            ->assertJsonPath('result.points', 300)
            ->assertJsonPath('result.order_points', 200)
            ->assertJsonPath('result.double_delivery_bonus', 100)
            ->assertJsonPath('result.delivery_points', 300)
            ->assertJsonPath('result.perfect_bonus', 0)
            ->assertJsonPath('result.batch_count', 1)
            ->assertJsonPath('result.orders_completed', 2);
    }

    public function test_restart_level_clears_fridge_and_preserves_pause_resume(): void
    {
        $progress = $this->progressAt(4);
        $this->startLevel(4)->assertOk();
        $order = $progress->refresh()->ipo_state['level_progress'][4]['runtime']['active_orders'][0];
        $this->factoryAction(4, 'factory_fridge_store', ['inventory' => [$order], 'item' => $order])->assertOk();
        $this->factoryAction(4, 'factory_pause')->assertOk()->assertJsonPath('result.paused', true);
        $this->factoryAction(4, 'factory_resume')->assertOk()->assertJsonPath('result.paused', false);

        $this->startLevel(4)->assertOk();
        $runtime = $progress->refresh()->ipo_state['level_progress'][4]['runtime'];
        $this->assertSame([null, null], $runtime['fridge']['slots']);
        $this->assertSame([], $runtime['inventory']);
        $this->assertSame(0, $runtime['score']);
        $this->assertSame(0, $runtime['combo']);
        $this->assertSame(0, $runtime['batch_count']);
        $this->assertCount(2, $runtime['active_orders']);
        $this->assertFalse($runtime['paused']);
    }

    public function test_factory_frenzy_combines_systems_and_uses_eight_order_target(): void
    {
        $progress = $this->progressAt(5);
        $this->startLevel(5)->assertOk();

        $this->get(route('tools.ko-ki.meeting-two.mission', 'level-5'))
            ->assertOk()
            ->assertSee('data-conveyor-item', false)
            ->assertSee('data-cleaner', false)
            ->assertSee('data-powerup', false)
            ->assertSee('Target 1800 poin + 4 Double Delivery');

        for ($batch = 1; $batch <= 4; $batch++) {
            $response = $this->completeDoubleBatch(5)->assertOk()->assertJsonPath('result.correct', true);
            $response->assertJsonPath('result.level_completed', $batch === 4);
        }

        $state = $progress->refresh()->ipo_state;
        $this->assertTrue($state['factory_finished']);
        $this->assertFalse($state['completed']);
        $this->assertSame([1, 2, 3, 4, 5], $state['completed_levels']);
        $this->assertNull($progress->completed_at);
        $this->assertContains($state['final_recipe'], array_keys(KoKiAdventureService::RECIPES));
        $this->assertContains($state['final_recipe'], $state['level_progress'][5]['runtime']['recipe_history']);
    }

    public function test_final_reveal_uses_a_played_recipe_and_two_short_exit_challenges(): void
    {
        $progress = $this->progressAt(5);
        $this->startLevel(5)->assertOk();
        for ($batch = 0; $batch < 4; $batch++) {
            $this->completeDoubleBatch(5)->assertOk();
        }
        $state = $progress->refresh()->ipo_state;
        $product = KoKiAdventureService::PRODUCTS[$state['final_recipe']]['label'];

        $this->get(route('tools.ko-ki.meeting-two.completed'))
            ->assertOk()
            ->assertSee('Final Reveal')
            ->assertSee($product)
            ->assertSee('Space ditekan')
            ->assertSee('Program membaca tombol')
            ->assertSee('Ko-Ki Bot melompat')
            ->assertSee('Exit Challenge 1 dari 2');

        $this->patchJson(route('tools.ko-ki.meeting-two.update'), [
            'action' => 'factory_exit_identify', 'answer' => 'program',
        ])->assertOk()->assertJsonPath('result.correct', false);
        $this->patchJson(route('tools.ko-ki.meeting-two.update'), [
            'action' => 'factory_exit_identify', 'answer' => 'space',
        ])->assertOk()->assertJsonPath('result.correct', true);
        $this->patchJson(route('tools.ko-ki.meeting-two.update'), [
            'action' => 'factory_exit_arrange', 'card_order' => ['process', 'input', 'output'],
        ])->assertOk()->assertJsonPath('result.correct', false);
        $this->patchJson(route('tools.ko-ki.meeting-two.update'), [
            'action' => 'factory_exit_arrange', 'card_order' => ['input', 'process', 'output'],
        ])->assertOk()
            ->assertJsonPath('result.correct', true)
            ->assertJsonPath('result.completed', true)
            ->assertJsonPath('result.badge', 'Factory Master');

        $progress->refresh();
        $this->assertNotNull($progress->completed_at);
        $this->assertSame('Factory Master', $progress->ipo_state['badge']);
        $this->get(route('tools.ko-ki.meeting-two.completed'))
            ->assertOk()->assertSee('Factory Master')->assertSee('Pertemuan 2 selesai');
    }

    public function test_factory_frenzy_powerups_are_saved(): void
    {
        $progress = $this->progressAt(5);
        Carbon::setTestNow('2026-08-12 10:00:00');
        $this->startLevel(5)->assertOk();
        $runtime = $progress->refresh()->ipo_state['level_progress'][5]['runtime'];
        $this->assertSame('speed_boost', $runtime['powerup']['type']);

        $this->factoryAction(5, 'factory_powerup', ['powerup' => 'speed_boost'])
            ->assertOk()->assertJsonPath('result.label', 'Speed Boost');
        $runtime = $progress->refresh()->ipo_state['level_progress'][5]['runtime'];
        $this->assertArrayHasKey('speed_boost', $runtime['effects']);
        $this->assertSame('big_bag', $runtime['powerup']['type']);

        Carbon::setTestNow('2026-08-12 10:00:16');
        $this->factoryAction(5, 'factory_powerup', ['powerup' => 'big_bag'])
            ->assertOk()->assertJsonPath('result.label', 'Big Bag');
        $this->assertArrayHasKey('big_bag', $progress->refresh()->ipo_state['level_progress'][5]['runtime']['effects']);
        Carbon::setTestNow();
    }

    public function test_completed_levels_can_be_replayed_without_losing_best_results(): void
    {
        $progress = $this->startFactory();
        $this->completeLevel(1);
        $before = $progress->refresh()->ipo_state['level_progress'][1];

        $this->startLevel(1)->assertOk();
        $after = $progress->refresh()->ipo_state['level_progress'][1];
        $this->assertTrue($after['completed']);
        $this->assertSame($before['best_score'], $after['best_score']);
        $this->assertSame(2, $after['attempts']);
        $this->assertSame(0, $after['runtime']['score']);
    }

    public function test_old_factory_state_is_preserved_as_legacy_data(): void
    {
        $progress = $this->startMeetingTwo();
        $legacy = ['adventure_version' => 3, 'intro_seen' => true, 'score' => 300];
        $progress->forceFill(['ipo_state' => $legacy])->save();

        $this->get(route('tools.ko-ki.meeting-two.activity'))
            ->assertRedirect(route('tools.ko-ki.meeting-two.map'));
        $this->startLevel(1)->assertOk();

        $state = $progress->refresh()->ipo_state;
        $this->assertSame($legacy, $state['legacy_state']);
        $this->assertSame(4, $state['adventure_version']);
    }

    public function test_factory_progress_requires_the_matching_student_session(): void
    {
        $this->get(route('tools.ko-ki.meeting-two.map'))
            ->assertRedirect(route('tools.ko-ki.meeting-two.welcome'));
        $this->patchJson(route('tools.ko-ki.meeting-two.update'), ['action' => 'complete_intro'])
            ->assertConflict();
    }

    public function test_factory_frenzy_requires_both_minimum_batches_and_target_score(): void
    {
        $progress = $this->progressAt(5);
        $this->startLevel(5)->assertOk();

        for ($batch = 1; $batch <= 4; $batch++) {
            $this->factoryAction(5, 'factory_deliver', ['item' => 'apple_juice'])
                ->assertOk()
                ->assertJsonPath('result.correct', false);
            $response = $this->completeDoubleBatch(5)->assertOk();
            $response->assertJsonPath('result.level_completed', false);
        }

        $runtime = $progress->refresh()->ipo_state['level_progress'][5]['runtime'];
        $this->assertSame(4, $runtime['batch_count']);
        $this->assertSame(1440, $runtime['score']);
        $this->assertTrue($runtime['active']);

        $this->completeDoubleBatch(5)
            ->assertOk()
            ->assertJsonPath('result.level_completed', true)
            ->assertJsonPath('result.perfect_bonus', 100)
            ->assertJsonPath('result.score', 2000);
    }

    private function startMeetingTwo(): KoKiProgress
    {
        $this->post(route('tools.ko-ki.meeting-two.start'), ['student_name' => 'Lala', 'grade' => 4])
            ->assertRedirect(route('tools.ko-ki.meeting-two.activity'));

        return KoKiProgress::query()->where('meeting', 2)->sole();
    }

    private function startFactory(): KoKiProgress
    {
        $progress = $this->startMeetingTwo();
        $this->completeIntro();

        return $progress;
    }

    private function completeIntro(): void
    {
        $this->patchJson(route('tools.ko-ki.meeting-two.update'), ['action' => 'complete_intro'])->assertOk();
    }

    private function progressAt(int $targetLevel): KoKiProgress
    {
        $progress = $this->startFactory();
        for ($level = 1; $level < $targetLevel; $level++) {
            $this->completeLevel($level);
            $this->assertNotNull(app(KoKiAdventureService::class)->approveNextLevel($progress->refresh(), $level + 1));
        }

        return $progress;
    }

    private function completeLevel(int $level): void
    {
        $this->startLevel($level)->assertOk();
        if ($level >= 4) {
            for ($batch = 0; $batch < KoKiAdventureService::LEVELS[$level]['target'] / 2; $batch++) {
                $this->completeDoubleBatch($level)->assertOk();
            }
        } else {
            for ($order = 0; $order < KoKiAdventureService::LEVELS[$level]['target']; $order++) {
                $this->deliverCurrentOrder($level)->assertOk();
            }
        }
        if ($level === 1) {
            $this->patchJson(route('tools.ko-ki.meeting-two.update'), ['action' => 'factory_training_replay'])->assertOk();
        }
    }

    private function startLevel(int $level): TestResponse
    {
        return $this->patchJson(route('tools.ko-ki.meeting-two.update'), [
            'action' => 'factory_start_level', 'level' => $level,
        ]);
    }

    private function deliverCurrentOrder(int $level): TestResponse
    {
        $progress = KoKiProgress::query()->where('meeting', 2)->latest('id')->firstOrFail();
        $item = $progress->ipo_state['level_progress'][$level]['runtime']['active_orders'][0];

        return $this->factoryAction($level, 'factory_deliver', ['inventory' => [$item], 'item' => $item]);
    }

    private function completeDoubleBatch(int $level): TestResponse
    {
        $progress = KoKiProgress::query()->where('meeting', 2)->latest('id')->firstOrFail();
        $orders = $progress->ipo_state['level_progress'][$level]['runtime']['active_orders'];

        $this->factoryAction($level, 'factory_fridge_store', ['inventory' => [$orders[0]], 'item' => $orders[0]])->assertOk();
        $this->factoryAction($level, 'factory_fridge_take', ['inventory' => [$orders[1]], 'fridge_slot' => 0])->assertOk();

        return $this->factoryAction($level, 'factory_double_deliver');
    }

    private function factoryAction(int $level, string $action, array $data = []): TestResponse
    {
        $progress = KoKiProgress::query()->where('meeting', 2)->latest('id')->firstOrFail();
        $runtime = $progress->ipo_state['level_progress'][$level]['runtime'];

        return $this->patchJson(route('tools.ko-ki.meeting-two.update'), [
            'action' => $action,
            'level' => $level,
            'player' => $runtime['player'],
            'inventory' => $runtime['inventory'],
            'blender' => $runtime['blender'],
            ...$data,
        ]);
    }
}
