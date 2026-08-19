<?php

namespace Tests\Feature;

use App\Models\KoKiProgress;
use App\Services\KoKiProgressService;
use App\Services\KoKiRescueProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WanKoKiMeetingThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_and_welcome_show_meeting_three_registration(): void
    {
        $this->get(route('tools.ko-ki.index'))->assertOk()->assertSee('Ko-Ki Rescue Mission');
        $this->get(route('tools.ko-ki.meeting-three.welcome'))
            ->assertOk()->assertSee('Nama panggilan')->assertSee('Kelas 4')->assertSee('Kelas 5');
    }

    public function test_only_grades_four_and_five_can_start_and_session_is_separate(): void
    {
        foreach ([1, 2, 3, 6] as $grade) {
            $this->post(route('tools.ko-ki.meeting-three.start'), ['student_name' => 'Naya', 'grade' => $grade])
                ->assertSessionHasErrors('grade');
        }
        $this->post(route('tools.ko-ki.start'), ['student_name' => 'P1', 'grade' => 4]);
        $meetingOneId = session(KoKiProgressService::SESSION_KEY);
        $this->post(route('tools.ko-ki.meeting-three.start'), ['student_name' => 'P3', 'grade' => 5])
            ->assertRedirect(route('tools.ko-ki.meeting-three.intro'));

        $this->assertSame($meetingOneId, session(KoKiProgressService::SESSION_KEY));
        $this->assertNotSame($meetingOneId, session('ko_ki.meeting_3.progress_id'));
        $this->assertDatabaseHas('ko_ki_progresses', ['student_name' => 'P3', 'grade' => 5, 'meeting' => 3]);
    }

    public function test_context_must_be_completed_before_map(): void
    {
        $this->get(route('tools.ko-ki.meeting-three.map'))->assertRedirect(route('tools.ko-ki.meeting-three.welcome'));
        $this->startMeeting();
        $this->get(route('tools.ko-ki.meeting-three.intro'))->assertOk()->assertSee('Jelas')->assertSee('Berurutan');
        $this->get(route('tools.ko-ki.meeting-three.map'))->assertRedirect(route('tools.ko-ki.meeting-three.intro'));
        $this->post(route('tools.ko-ki.meeting-three.intro.complete'))->assertRedirect(route('tools.ko-ki.meeting-three.map'));
        $this->get(route('tools.ko-ki.meeting-three.map'))->assertOk()->assertSee('tunggu izin admin');
    }

    public function test_next_level_requires_admin_approval_and_completion_is_saved_once(): void
    {
        $progress = $this->readyMeeting();
        $this->get(route('tools.ko-ki.meeting-three.mission', 1))->assertOk()->assertSee('RUN PROGRAM');
        $this->get(route('tools.ko-ki.meeting-three.mission', 2))->assertRedirect(route('tools.ko-ki.meeting-three.map'));

        $this->completeLevel(1)->assertOk()->assertJsonPath('stars', 3);
        $this->assertSame([1], $progress->refresh()->completed_levels);
        $this->assertSame(1, $progress->attempt_count);
        $this->get(route('tools.ko-ki.meeting-three.mission', 2))->assertRedirect(route('tools.ko-ki.meeting-three.map'));

        $this->assertNotNull(app(KoKiRescueProgressService::class)->approveNextLevel($progress, 2));
        $this->get(route('tools.ko-ki.meeting-three.mission', 2))->assertOk()->assertSee('data-rescue-game', false);
    }

    public function test_level_cannot_be_skipped_through_progress_endpoint(): void
    {
        $this->readyMeeting();
        $this->completeLevel(2)->assertUnprocessable()->assertJsonValidationErrors('level');
    }

    public function test_final_unlocks_evaluation_and_correct_answers_award_medal(): void
    {
        $progress = $this->readyMeeting();
        foreach (range(1, 6) as $level) {
            $this->completeLevel($level)->assertOk();
            if ($level < 6) {
                app(KoKiRescueProgressService::class)->approveNextLevel($progress->refresh(), $level + 1);
            }
        }

        $this->get(route('tools.ko-ki.meeting-three.evaluation'))->assertOk()->assertSee('3 Tantangan');
        $this->post(route('tools.ko-ki.meeting-three.evaluation.submit'), [
            'clear_instruction' => 'ikuti_persis', 'missing_command' => 'ambil_air', 'sequence' => 'ambil-maju-siram',
        ])->assertRedirect(route('tools.ko-ki.meeting-three.evaluation'));
        $this->get(route('tools.ko-ki.meeting-three.evaluation'))
            ->assertOk()->assertSee('Komandan Rescue Ko-Ki')->assertSee('Naya');
        $this->assertNotNull($progress->refresh()->completed_at);
    }

    public function test_unknown_rescue_mission_returns_not_found(): void
    {
        $this->get('/tools/wan-ko-ki/pertemuan-3/misi/7')->assertNotFound();
    }

    private function startMeeting(): KoKiProgress
    {
        $this->post(route('tools.ko-ki.meeting-three.start'), ['student_name' => 'Naya', 'grade' => 4]);

        return KoKiProgress::query()->where('meeting', 3)->latest('id')->firstOrFail();
    }

    private function readyMeeting(): KoKiProgress
    {
        $progress = $this->startMeeting();
        $this->post(route('tools.ko-ki.meeting-three.intro.complete'));

        return $progress->refresh();
    }

    private function completeLevel(int $level)
    {
        $positions = KoKiRescueProgressService::FINAL_POSITIONS;

        return $this->patchJson(route('tools.ko-ki.meeting-three.progress'), [
            'level' => $level,
            'program' => ['TAKE_WATER'],
            'prediction' => $positions[$level],
        ]);
    }
}
