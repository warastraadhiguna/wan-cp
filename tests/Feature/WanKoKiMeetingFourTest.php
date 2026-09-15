<?php

namespace Tests\Feature;

use App\Models\KoKiProgress;
use App\Services\KoKiDetectiveProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WanKoKiMeetingFourTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_and_welcome_show_bug_detective_agency(): void
    {
        $this->get(route('tools.ko-ki.index'))->assertOk()->assertSee('Pertemuan 4')->assertSee('Bug Detective Agency');
        $this->get(route('tools.ko-ki.meeting-four.welcome'))
            ->assertOk()->assertSee('Nama panggilan')->assertSee('Kelas 4')->assertSee('Kelas 5');
    }

    public function test_only_grade_four_and_five_can_start_a_separate_meeting(): void
    {
        foreach ([1, 2, 3, 6] as $grade) {
            $this->post(route('tools.ko-ki.meeting-four.start'), ['student_name' => 'Naya', 'grade' => $grade])
                ->assertSessionHasErrors('grade');
        }

        $this->post(route('tools.ko-ki.meeting-four.start'), ['student_name' => 'Naya', 'grade' => 4])
            ->assertRedirect(route('tools.ko-ki.meeting-four.intro'));
        $this->assertDatabaseHas('ko_ki_progresses', ['student_name' => 'Naya', 'grade' => 4, 'meeting' => 4]);
        $this->assertNotNull(session('ko_ki.meeting_4.progress_id'));
    }

    public function test_intro_mystery_is_saved_before_case_one_opens(): void
    {
        $progress = $this->startMeeting();
        $this->get(route('tools.ko-ki.meeting-four.map'))->assertRedirect(route('tools.ko-ki.meeting-four.intro'));
        $this->get(route('tools.ko-ki.meeting-four.intro'))
            ->assertOk()->assertSee('Robotnya nakal')->assertSee('Ada bug di program')->assertSee('CASE #001');
        $this->post(route('tools.ko-ki.meeting-four.intro.begin'), ['answer' => 'ada_bug'])
            ->assertRedirect(route('tools.ko-ki.meeting-four.case', 1));

        $this->assertSame(['bug_mystery' => 'ada_bug'], $progress->refresh()->initial_answer);
    }

    public function test_all_cases_use_the_reusable_detective_toolbar(): void
    {
        $progress = $this->readyMeeting();
        foreach (range(1, 16) as $case) {
            if ($case > 1) {
                app(KoKiDetectiveProgressService::class)->approveNextCase($progress->refresh(), $case);
            }
            $this->get(route('tools.ko-ki.meeting-four.case', $case))
                ->assertOk()->assertSee('data-detective-game', false)
                ->assertSee('Replay')->assertSee('Step')->assertSee('Trace')
                ->assertSee('Mark Suspect')->assertSee('Repair')->assertSee('Test Fix');
            $this->completeCase($case)->assertOk()->assertJsonPath('state.collection_count', min(15, $case));
        }
        $this->assertNull($progress->refresh()->completed_at);
        $this->get(route('tools.ko-ki.meeting-four.notebook'))->assertOk()->assertSee('Detective Notebook');
        $this->post(route('tools.ko-ki.meeting-four.notebook.complete'), ['fix' => 'TURN_RIGHT'])
            ->assertRedirect(route('tools.ko-ki.meeting-four.notebook'));
        $this->assertNotNull($progress->refresh()->completed_at);
        $this->get(route('tools.ko-ki.meeting-four.notebook'))->assertOk()->assertSee('Bug Detective');
    }

    public function test_meeting_four_uses_soft_navigation_to_keep_fullscreen_between_cases(): void
    {
        $this->readyMeeting();

        $this->get(route('tools.ko-ki.meeting-four.case', 1))
            ->assertOk()
            ->assertSee('koki:before-soft-navigation', false)
            ->assertSee('window.history.pushState', false);
    }

    public function test_cases_unlock_in_order_and_cannot_be_skipped(): void
    {
        $progress = $this->readyMeeting();
        $this->assertSame([1], $progress->approved_levels);
        $this->get(route('tools.ko-ki.meeting-four.case', 2))
            ->assertRedirect(route('tools.ko-ki.meeting-four.map'))
            ->assertSessionHas('status', 'Case ini masih menunggu izin admin.');
        $this->completeCase(2, ['without_approval' => true])->assertUnprocessable()->assertJsonValidationErrors('case');
        $this->completeCase(1)->assertOk();
        $this->get(route('tools.ko-ki.meeting-four.case', 2))->assertRedirect(route('tools.ko-ki.meeting-four.map'));
        $this->assertSame(2, app(KoKiDetectiveProgressService::class)->nextApprovableCase($progress->refresh()));
        $this->assertNotNull(app(KoKiDetectiveProgressService::class)->approveNextCase($progress, 2));
        $this->get(route('tools.ko-ki.meeting-four.case', 2))->assertOk();
    }

    public function test_unused_case_approval_can_be_revoked(): void
    {
        $progress = $this->readyMeeting();
        $this->completeCase(1)->assertOk();
        $service = app(KoKiDetectiveProgressService::class);
        $service->approveNextCase($progress->refresh(), 2);
        $this->assertContains(2, $service->state($progress->refresh())['approved_cases']);

        $this->assertNotNull($service->revokeLatestCase($progress->refresh()));
        $this->assertNotContains(2, $service->state($progress->refresh())['approved_cases']);
    }

    public function test_server_rejects_a_repair_that_does_not_solve_the_case(): void
    {
        $this->readyMeeting();
        $this->patchJson(route('tools.ko-ki.meeting-four.progress'), [
            'case' => 1,
            'program' => ['MOVE_FORWARD', 'MOVE_FORWARD', 'TURN_LEFT', 'MOVE_FORWARD'],
            'suspects' => [2],
            'hint_count' => 1,
            'test_attempts' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('program');
    }

    public function test_progress_endpoint_only_updates_the_student_in_the_matching_session(): void
    {
        $current = $this->readyMeeting();
        $other = KoKiProgress::query()->create([
            'student_name' => 'Siswa lain', 'grade' => 4, 'semester' => 1, 'unit' => 1, 'meeting' => 4,
            'last_phase' => 'detective_case', 'current_level' => 1, 'completed_levels' => [], 'started_at' => now(),
        ]);

        $this->completeCase(1, ['progress_id' => $other->id])->assertOk();
        $this->assertSame([1], $current->refresh()->completed_levels);
        $this->assertSame([], $other->refresh()->completed_levels);
    }

    public function test_completed_cases_build_the_visual_bug_collection(): void
    {
        $this->readyMeeting();
        $this->completeCase(1)->assertOk();
        $this->completeCase(2)->assertOk();
        $this->completeCase(3)->assertOk();

        $this->get(route('tools.ko-ki.meeting-four.map'))
            ->assertOk()->assertSee('Bug Collection')->assertSee('3/15');
    }

    public function test_stars_reward_few_hints_and_few_test_attempts_without_blocking_progress(): void
    {
        $progress = $this->readyMeeting();
        $this->completeCase(1)->assertOk()->assertJsonPath('state.cases.1.stars', 3);
        $this->completeCase(2, ['hint_count' => 3, 'test_attempts' => 4])
            ->assertOk()->assertJsonPath('state.cases.2.stars', 1);

        $this->assertSame([1, 2], $progress->refresh()->completed_levels);
        app(KoKiDetectiveProgressService::class)->approveNextCase($progress->refresh(), 3);
        $this->get(route('tools.ko-ki.meeting-four.case', 3))->assertOk();
    }

    public function test_notebook_is_locked_until_final_case_and_mini_challenge_awards_badge(): void
    {
        $progress = $this->readyMeeting();
        $this->get(route('tools.ko-ki.meeting-four.notebook'))->assertOk();
        foreach (range(1, 16) as $case) {
            $this->completeCase($case)->assertOk();
        }

        $this->post(route('tools.ko-ki.meeting-four.notebook.complete'), ['fix' => 'TURN_LEFT'])
            ->assertRedirect(route('tools.ko-ki.meeting-four.notebook'));
        $this->assertNull($progress->refresh()->completed_at);
        $this->post(route('tools.ko-ki.meeting-four.notebook.complete'), ['fix' => 'TURN_RIGHT']);
        $state = app(KoKiDetectiveProgressService::class)->state($progress->refresh());
        $this->assertTrue($state['completed']);
        $this->assertSame('Bug Detective', $state['badge']);
    }

    public function test_unknown_case_returns_not_found(): void
    {
        $this->get('/tools/wan-ko-ki/pertemuan-4/kasus/17')->assertNotFound();
    }

    public function test_batch_sync_saves_multiple_completed_cases_in_one_request(): void
    {
        $progress = $this->readyMeeting();
        $progress->forceFill(['approved_levels' => [1, 2, 3]])->save();
        $cases = collect([1, 2, 3])->map(fn (int $case): array => [
            'case' => $case,
            'program' => KoKiDetectiveProgressService::SOLUTIONS[$case],
            'suspects' => [],
            'hint_count' => 1,
            'test_attempts' => 2,
        ])->all();

        $this->postJson(route('tools.ko-ki.meeting-four.sync'), ['cases' => $cases])
            ->assertOk()->assertJsonPath('state.completed_cases', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $progress->refresh()->completed_levels);
    }

    public function test_detective_rank_grows_at_cases_three_six_nine_and_ten(): void
    {
        $progress = $this->readyMeeting();
        foreach (range(1, 10) as $case) {
            $this->completeCase($case)->assertOk();
            if (in_array($case, [3, 6, 9, 10], true)) {
                $expected = [3 => 'Junior Detective', 6 => 'Bug Investigator', 9 => 'Senior Bug Detective', 10 => 'Master Detective'][$case];
                $this->assertSame($expected, app(KoKiDetectiveProgressService::class)->state($progress->refresh())['rank']);
            }
        }
    }

    public function test_legacy_case_six_final_progress_moves_to_case_sixteen_without_data_loss(): void
    {
        $progress = $this->readyMeeting();
        $states = $progress->level_states;
        $states[6] = [
            'program' => KoKiDetectiveProgressService::SOLUTIONS[16],
            'stars' => 3,
            'attempts' => 1,
        ];
        $progress->forceFill(['level_states' => $states, 'completed_levels' => [1, 2, 3, 4, 5, 6]])->save();

        $state = app(KoKiDetectiveProgressService::class)->state($progress->refresh());
        $this->assertSame([1, 2, 3, 4, 5, 16], $state['completed_cases']);
        $this->assertSame(6, $state['current_case']);
        $this->assertSame(KoKiDetectiveProgressService::SOLUTIONS[16], $state['cases'][16]['program']);
        $this->assertFalse($state['final_solved']);

        $this->completeCase(6)->assertOk();
        $this->assertSame([1, 2, 3, 4, 5, 6, 16], $progress->refresh()->completed_levels);
    }

    public function test_previous_case_eleven_final_progress_moves_to_case_sixteen_and_opens_new_cases(): void
    {
        $progress = $this->readyMeeting();
        $states = $progress->level_states;
        $states[11] = [
            'program' => KoKiDetectiveProgressService::SOLUTIONS[16],
            'stars' => 3,
            'attempts' => 1,
        ];
        $progress->forceFill([
            'level_states' => $states,
            'completed_levels' => range(1, 11),
        ])->save();

        $state = app(KoKiDetectiveProgressService::class)->state($progress->refresh());
        $this->assertSame([...range(1, 10), 16], $state['completed_cases']);
        $this->assertSame(11, $state['current_case']);
        $this->assertSame(KoKiDetectiveProgressService::SOLUTIONS[16], $state['cases'][16]['program']);
        $this->assertFalse($state['cases'][11]['completed']);
        $this->assertFalse($state['final_solved']);
    }

    private function startMeeting(): KoKiProgress
    {
        $this->post(route('tools.ko-ki.meeting-four.start'), ['student_name' => 'Naya', 'grade' => 4]);

        return KoKiProgress::query()->where('meeting', 4)->latest('id')->firstOrFail();
    }

    private function readyMeeting(): KoKiProgress
    {
        $progress = $this->startMeeting();
        $this->post(route('tools.ko-ki.meeting-four.intro.begin'), ['answer' => 'ada_bug']);

        return $progress->refresh();
    }

    private function completeCase(int $case, array $extra = [])
    {
        $withoutApproval = (bool) ($extra['without_approval'] ?? false);
        unset($extra['without_approval']);
        if ($case > 1 && ! $withoutApproval) {
            $progress = KoKiProgress::query()->findOrFail(session('ko_ki.meeting_4.progress_id'));
            app(KoKiDetectiveProgressService::class)->approveNextCase($progress, $case);
        }

        return $this->patchJson(route('tools.ko-ki.meeting-four.progress'), [
            'case' => $case,
            'program' => KoKiDetectiveProgressService::SOLUTIONS[$case],
            'suspects' => match ($case) {
                1 => [2], 2 => [1, 2], 3 => [2], 4 => [1, 2], 5 => [0],
                6 => [2], 7 => [2], 8 => [3], 9 => [1], 10 => [0, 1, 2],
                11 => [2], 12 => [0, 1], 13 => [2], 14 => [1], 15 => [1, 3], 16 => [3, 7],
            },
            'hint_count' => 0,
            'test_attempts' => 1,
            ...$extra,
        ]);
    }
}
