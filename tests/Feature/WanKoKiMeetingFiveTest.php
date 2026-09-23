<?php

namespace Tests\Feature;

use App\Models\KoKiProgress;
use App\Services\KoKiCreatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WanKoKiMeetingFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_entry_and_welcome_are_available(): void
    {
        $this->get(route('tools.ko-ki.index'))->assertOk()->assertSee('Pertemuan 5')->assertSee('Ko-Ki Creator Lab');
        $this->get(route('tools.ko-ki.meeting-five.welcome'))->assertOk()->assertSee('Nama panggilan')->assertSee('Kelas 4');
        $this->get(route('tools.ko-ki.meeting-five.lab'))->assertRedirect(route('tools.ko-ki.meeting-five.welcome'));
    }

    public function test_start_requires_grade_four_or_five(): void
    {
        $this->post(route('tools.ko-ki.meeting-five.start'), ['student_name' => 'Ari', 'grade' => 3])->assertSessionHasErrors('grade');
        $this->post(route('tools.ko-ki.meeting-five.start'), ['student_name' => 'Ari', 'grade' => 4])->assertRedirect(route('tools.ko-ki.meeting-five.lab'));
        $this->assertDatabaseHas('ko_ki_progresses', ['student_name' => 'Ari', 'grade' => 4, 'meeting' => 5]);
    }

    public function test_lab_shows_visual_editor_and_playable_arena(): void
    {
        $this->start();
        $this->get(route('tools.ko-ki.meeting-five.lab'))->assertOk()
            ->assertSee('data-creator-lab', false)->assertSee('data-arena', false)
            ->assertSee('data-second-item', false)->assertSee('data-second-obstacle', false)
            ->assertSee('data-level-result', false)
            ->assertSee('data-creator-finale', false)->assertSee('Evaluasi Game Creator')
            ->assertSee('Ko-Ki Game Creator')->assertSee('Medali kelulusan')
            ->assertDontSee('data-world-stage', false)
            ->assertSee('MAIN GAME-KU')->assertSee('MAIN BERGANTIAN')
            ->assertSee('Seru')->assertSee('Menantang')
            ->assertSee('TEST GAME')->assertSee('Buat gim, lalu coba sendiri');
    }

    public function test_core_and_meeting_completion_each_save_in_one_checkpoint(): void
    {
        $progress = $this->start();
        $this->completeSetups($progress);
        $this->assertSame([1, 2, 3, 4], $progress->refresh()->completed_levels);
        $this->assertNull($progress->completed_at);
        $this->assertSame('playtest', $progress->last_phase);
        $this->approveNextCreatorStep($progress);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->playtestedPayload())
            ->assertOk()->assertJsonPath('state.playtested', true);
        $this->assertNotNull($progress->refresh()->completed_at);
        $this->get(route('tools.ko-ki.meeting-five.lab'))->assertOk()->assertSee('"tested_win":true', false);
    }

    public function test_exit_can_save_partial_draft_and_stale_snapshot_cannot_go_backwards(): void
    {
        $progress = $this->start();
        $draft = $this->payload(2);
        $draft['character'] = 'cat';
        $this->approveNextCreatorStep($progress);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload(1))->assertOk();
        $this->approveNextCreatorStep($progress);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $draft)
            ->assertOk()->assertJsonPath('state.stage', 2)->assertJsonPath('state.character', 'cat');
        $this->approveNextCreatorStep($progress);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload(3))->assertOk();
        $this->approveNextCreatorStep($progress);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload(4))->assertOk()->assertJsonPath('state.stage', 4);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $draft)
            ->assertOk()->assertJsonPath('state.stage', 4);
        $this->assertSame([1, 2, 3, 4], $progress->refresh()->completed_levels);
    }

    public function test_playtest_requires_all_checks_and_bonuses_are_optional_after_game_ready(): void
    {
        $progress = $this->start();
        $this->completeSetups($progress);
        $premature = $this->payload(4);
        $premature['bonuses'] = ['second_collectible' => true, 'special_plus_three' => false, 'second_obstacle' => false, 'sound' => false, 'timer' => false, 'target' => false];
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $premature)->assertUnprocessable()->assertJsonValidationErrors('bonuses');

        $this->approveNextCreatorStep($progress);
        $incomplete = $this->playtestedPayload();
        $incomplete['playtest_checks']['obstacle'] = false;
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $incomplete)->assertUnprocessable()->assertJsonValidationErrors('playtested');

        $ready = $this->playtestedPayload();
        $ready['bonuses'] = ['second_collectible' => true, 'special_plus_three' => true, 'second_obstacle' => true, 'sound' => true, 'timer' => true, 'target' => true];
        $ready['target_score'] = 15;
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $ready)
            ->assertOk()->assertJsonPath('state.bonuses.special_plus_three', true)->assertJsonPath('state.target_score', 15);
        $this->assertSame('completed', $progress->refresh()->last_phase);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload(4))
            ->assertOk()->assertJsonPath('state.playtested', true)->assertJsonPath('state.target_score', 15);
    }

    public function test_existing_four_challenge_progress_can_continue_to_playtest(): void
    {
        $progress = $this->start();
        $progress->forceFill([
            'level_states' => ['_creator' => $this->payload(4)],
            'completed_levels' => [1, 2, 3, 4],
            'completed_at' => now(),
        ])->save();

        $this->get(route('tools.ko-ki.meeting-five.lab'))->assertOk()->assertSee('"stage":4', false)->assertSee('"playtested":false', false);
        $this->approveNextCreatorStep($progress);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->playtestedPayload())
            ->assertOk()->assertJsonPath('state.playtested', true);
        $this->assertSame([1, 2, 3, 4], $progress->refresh()->completed_levels);
    }

    public function test_invalid_mapping_and_collectible_rule_are_rejected(): void
    {
        $this->start();
        $this->approveNextCreatorStep(KoKiProgress::query()->where('meeting', 5)->latest('id')->firstOrFail());
        $duplicate = $this->payload(1);
        $duplicate['controls']['right'] = 'LEFT';
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $duplicate)->assertUnprocessable()->assertJsonValidationErrors('controls');
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload(1))->assertOk();
        $this->approveNextCreatorStep(KoKiProgress::query()->where('meeting', 5)->latest('id')->firstOrFail());
        $missingRule = $this->payload(2);
        $missingRule['collect_rule'] = 'none';
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $missingRule)->assertUnprocessable()->assertJsonValidationErrors('collect_rule');
    }

    public function test_session_cannot_change_another_students_work(): void
    {
        $current = $this->start();
        $other = KoKiProgress::query()->create([
            'student_name' => 'Siswa lain', 'grade' => 5, 'semester' => 1, 'unit' => 1, 'meeting' => 5,
            'last_phase' => 'creator_lab', 'current_level' => 1, 'completed_levels' => [], 'started_at' => now(),
        ]);
        $this->approveNextCreatorStep($current);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), [...$this->payload(1), 'progress_id' => $other->id])->assertOk();
        $this->assertSame([1], $current->refresh()->completed_levels);
        $this->assertSame([], $other->refresh()->completed_levels);
    }

    public function test_ten_levels_and_evaluation_unlock_the_creator_medal(): void
    {
        $progress = $this->start();
        $this->completeSetups($progress);
        $this->approveNextCreatorStep($progress);

        $this->postJson(route('tools.ko-ki.meeting-five.adventure.complete'), [
            'completed_levels' => range(1, 9),
            'answers' => ['creator' => 'mengatur_game', 'debug' => 'uji_perbaiki'],
        ])->assertUnprocessable();

        $this->postJson(route('tools.ko-ki.meeting-five.adventure.complete'), [
            'completed_levels' => range(1, 10),
            'answers' => ['creator' => 'mengatur_game', 'debug' => 'uji_perbaiki'],
        ])->assertOk()->assertJsonPath('adventure.completed', true)->assertJsonPath('adventure.medal', 'Ko-Ki Game Creator');

        $progress->refresh();
        $this->assertSame('completed', $progress->last_phase);
        $this->assertSame(10, $progress->current_level);
        $this->assertSame(range(1, 10), $progress->completed_levels);
        $this->assertNotNull($progress->completed_at);
    }

    public function test_playtest_waits_for_admin_approval_after_setup(): void
    {
        $progress = $this->start();

        foreach (range(1, 4) as $stage) {
            $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload($stage))
                ->assertForbidden()->assertJsonPath('message', 'Setup berikutnya masih menunggu izin admin.');
            $this->getJson(route('tools.ko-ki.meeting-five.playtest.status'))
                ->assertOk()->assertJsonPath('next_step', $stage)
                ->assertJsonPath('approved_steps', $stage === 1 ? [] : range(1, $stage - 1));
            $this->approveNextCreatorStep($progress);
            $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload($stage))
                ->assertOk()->assertJsonPath('state.stage', $stage);
        }

        $this->getJson(route('tools.ko-ki.meeting-five.playtest.status'))
            ->assertOk()->assertJsonPath('setup_completed', true)->assertJsonPath('approved', false)
            ->assertJsonPath('next_step', 5);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->playtestedPayload())
            ->assertForbidden()->assertJsonPath('message', 'Playtest masih menunggu izin admin.');
        $this->postJson(route('tools.ko-ki.meeting-five.adventure.complete'), [
            'completed_levels' => range(1, 10),
            'answers' => ['creator' => 'mengatur_game', 'debug' => 'uji_perbaiki'],
        ])->assertForbidden();

        $this->approveNextCreatorStep($progress);
        $this->getJson(route('tools.ko-ki.meeting-five.playtest.status'))
            ->assertOk()->assertJsonPath('approved', true);
        $this->get(route('tools.ko-ki.meeting-five.lab'))
            ->assertOk()->assertSee('data-playtest-approved="1"', false)
            ->assertSee('data-playtest-status-url=', false);
        $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->playtestedPayload())
            ->assertOk()->assertJsonPath('state.playtested', true);
    }

    private function start(): KoKiProgress
    {
        $this->post(route('tools.ko-ki.meeting-five.start'), ['student_name' => 'Ari', 'grade' => 4]);

        return KoKiProgress::query()->where('meeting', 5)->latest('id')->firstOrFail();
    }

    private function payload(int $stage): array
    {
        return [
            'stage' => $stage, 'character' => 'koki', 'theme' => 'green',
            'controls' => ['left' => 'LEFT', 'right' => 'RIGHT', 'jump' => 'SPACE'],
            'collectible' => 'leaf', 'collect_rule' => $stage >= 2 ? 'score_move' : 'none',
            'obstacle' => 'rock', 'obstacle_rule' => 'start', 'target_score' => 3,
            'tested_win' => $stage === 4,
        ];
    }

    private function playtestedPayload(): array
    {
        return [
            ...$this->payload(4),
            'playtested' => true,
            'playtest_checks' => ['control' => true, 'collectible' => true, 'score' => true, 'obstacle' => true, 'win' => true],
        ];
    }

    private function completeSetups(KoKiProgress $progress): void
    {
        foreach (range(1, 4) as $stage) {
            $this->approveNextCreatorStep($progress);
            $this->patchJson(route('tools.ko-ki.meeting-five.progress'), $this->payload($stage))
                ->assertOk()->assertJsonPath('state.stage', $stage);
        }
    }

    private function approveNextCreatorStep(KoKiProgress $progress): void
    {
        $this->assertNotNull(app(KoKiCreatorService::class)->approveNextStep($progress->refresh()));
    }
}
