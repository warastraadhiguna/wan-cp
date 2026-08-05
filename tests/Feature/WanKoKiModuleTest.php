<?php

namespace Tests\Feature;

use App\Models\KoKiProgress;
use App\Services\KoKiLaboratoryService;
use App\Services\KoKiProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WanKoKiModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_is_public_and_contains_csrf_protection(): void
    {
        $this->get(route('tools.ko-ki.welcome'))
            ->assertOk()
            ->assertSee('Halo! Kenalan dengan Ko-Ki Bot')
            ->assertSee('maksimal 60 menit')
            ->assertSee('name="_token"', false);
    }

    public function test_student_starts_with_nickname_and_grade_then_sees_opening(): void
    {
        $this->post(route('tools.ko-ki.start'), [
            'student_name' => '  <b>Naya</b>  ',
            'grade' => 4,
        ])->assertRedirect(route('tools.ko-ki.opening'))
            ->assertSessionHas(KoKiProgressService::SESSION_KEY);

        $this->assertDatabaseHas('ko_ki_progresses', [
            'user_id' => null,
            'student_name' => 'Naya',
            'grade' => 4,
            'last_phase' => 'opening',
        ]);

        $this->get(route('tools.ko-ki.opening'))
            ->assertOk()
            ->assertSee('WAn Ko-Ki')
            ->assertSee('Ko-Ki Bot: Komputer Paling Patuh')
            ->assertSee('Halo! Aku Ko-Ki Bot.')
            ->assertSee('Hari ini kita akan mencari tahu bagaimana komputer bekerja.')
            ->assertSee('Mulai Petualangan');
    }

    public function test_grade_must_be_four_or_five(): void
    {
        $this->from(route('tools.ko-ki.welcome'))
            ->post(route('tools.ko-ki.start'), [
                'student_name' => 'Naya',
                'grade' => 6,
            ])
            ->assertRedirect(route('tools.ko-ki.welcome'))
            ->assertSessionHasErrors(['grade']);

        $this->assertDatabaseCount('ko_ki_progresses', 0);
    }

    public function test_only_one_initial_question_is_shown_per_screen(): void
    {
        $this->startStudent();

        $this->post(route('tools.ko-ki.questions.start'))
            ->assertRedirect(route('tools.ko-ki.questions.show', 1));

        $this->get(route('tools.ko-ki.questions.show', 1))
            ->assertOk()
            ->assertSee('1 dari 4')
            ->assertSee('Apakah komputer lebih pintar daripada manusia?')
            ->assertSee('Keduanya memiliki kemampuan berbeda')
            ->assertDontSee('Apakah komputer dapat mengetahui keinginan kita tanpa diberi tahu?');
    }

    public function test_answers_are_saved_and_progress_resumes_after_refresh(): void
    {
        $this->startStudent();
        $this->post(route('tools.ko-ki.questions.start'));

        $this->post(route('tools.ko-ki.questions.answer', 1), [
            'answer' => 'different',
        ])->assertRedirect(route('tools.ko-ki.questions.show', 2));

        $progress = KoKiProgress::query()->sole();

        $this->assertSame([1 => 'different'], $progress->initial_answer);
        $this->assertSame('question_2', $progress->last_phase);

        $this->get(route('tools.ko-ki.questions.show', 2))
            ->assertOk()
            ->assertSee('2 dari 4')
            ->assertSee('Apakah komputer dapat mengetahui keinginan kita tanpa diberi tahu?')
            ->assertDontSee('Mengapa karakter gim dapat bergerak?');

        $this->get(route('tools.ko-ki.welcome'))
            ->assertOk()
            ->assertSee(route('tools.ko-ki.questions.show', 2), false);
    }

    public function test_question_rejects_an_unknown_answer_without_judging_it(): void
    {
        $this->startStudent();
        $this->post(route('tools.ko-ki.questions.start'));

        $this->from(route('tools.ko-ki.questions.show', 1))
            ->post(route('tools.ko-ki.questions.answer', 1), [
                'answer' => 'invalid-answer',
            ])
            ->assertRedirect(route('tools.ko-ki.questions.show', 1))
            ->assertSessionHasErrors(['answer']);

        $this->assertNull(KoKiProgress::query()->sole()->initial_answer);
    }

    public function test_all_answers_lead_to_the_saved_confirmation(): void
    {
        $this->startStudent();
        $this->answerAllInitialQuestions();

        $progress = KoKiProgress::query()->sole();

        $this->assertSame([
            1 => 'unknown',
            2 => 'no',
            3 => 'program',
            4 => 'programmer',
        ], $progress->initial_answer);
        $this->assertSame('questions_completed', $progress->last_phase);

        $this->get(route('tools.ko-ki.questions.completed'))
            ->assertOk()
            ->assertSee('Jawabanmu sudah disimpan.')
            ->assertSee('Sekarang kita buktikan melalui eksperimen.')
            ->assertDontSee('skor', false);
    }

    public function test_mission_progress_can_be_saved_and_completed_without_losing_initial_answers(): void
    {
        $this->startStudent();
        $this->answerAllInitialQuestions();
        $this->post(route('tools.ko-ki.activity.start'))
            ->assertRedirect(route('tools.ko-ki.activity'));

        $this->patch(route('tools.ko-ki.progress.update'), [
            'last_phase' => 'activity',
            'current_level' => 1,
            'last_program' => ['MOVE_FORWARD', 'TURN_RIGHT'],
            'increment_attempt' => true,
            'completed_level' => 1,
        ])->assertRedirect(route('tools.ko-ki.activity'));

        $progress = KoKiProgress::query()->sole();

        $this->assertCount(4, $progress->initial_answer);
        $this->assertSame([1], $progress->completed_levels);
        $this->assertSame(['MOVE_FORWARD', 'TURN_RIGHT'], $progress->last_program);
        $this->assertSame([1 => ['MOVE_FORWARD', 'TURN_RIGHT']], $progress->level_programs);
        $this->assertSame([1 => 1], $progress->level_attempts);
        $this->assertSame(1, $progress->attempt_count);

        $this->get(route('tools.ko-ki.activity'))
            ->assertOk()
            ->assertSee('Aku bergerak karena kamu memberiku perintah.')
            ->assertSee('Putar hanya mengubah arahku.')
            ->assertSee('Langkah Satu per Satu')
            ->assertSee('Hapus Semua');
    }

    public function test_progress_endpoint_rejects_unknown_game_commands(): void
    {
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Dito',
            'grade' => 4,
            'last_phase' => 'activity',
            'started_at' => now(),
        ]);

        $this->withSession([KoKiProgressService::SESSION_KEY => $progress->getKey()])
            ->patchJson(route('tools.ko-ki.progress.update'), [
                'last_phase' => 'activity',
                'current_level' => 1,
                'last_program' => ['JUMP'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['last_program.0']);
    }

    public function test_student_can_complete_the_meeting(): void
    {
        $this->startStudent('Raka', 5);
        $this->answerAllInitialQuestions();
        $this->post(route('tools.ko-ki.activity.start'));

        KoKiProgress::query()->sole()->forceFill([
            'completed_levels' => [1, 2, 3, 4],
            'level_states' => [3 => ['corrected_bug' => true]],
            'lab_state' => ['completed' => true],
        ])->save();

        $this->post(route('tools.ko-ki.finish'), $this->validCompletionPayload())
            ->assertRedirect(route('tools.ko-ki.completed'));

        $progress = KoKiProgress::query()->sole();

        $this->assertSame('completed', $progress->last_phase);
        $this->assertNotNull($progress->completed_at);
        $this->assertSame([
            'q1' => 'not_quite',
            'q2' => 'true',
            'q3' => 'not_quite',
        ], $progress->exit_ticket_answers);
        $this->assertSame('Agar tahu apa yang harus dilakukan', $progress->reflection_choice);

        $this->get(route('tools.ko-ki.completed'))
            ->assertOk()
            ->assertSee('Pemberi Instruksi Pemula')
            ->assertSee('Hari ini kamu sudah membuat program pertamamu.')
            ->assertSee('Kamu berpikir, Ko-Ki Bot menjalankan!')
            ->assertSee('Bug diperbaiki');
    }

    public function test_progress_is_stored_separately_for_each_level(): void
    {
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Sari',
            'grade' => 5,
            'last_phase' => 'activity',
            'started_at' => now(),
        ]);

        $this->withSession([KoKiProgressService::SESSION_KEY => $progress->getKey()]);

        $this->patchJson(route('tools.ko-ki.progress.update'), [
            'last_phase' => 'activity',
            'current_level' => 1,
            'last_program' => ['MOVE_FORWARD', 'MOVE_FORWARD'],
            'increment_attempt' => true,
            'completed_level' => 1,
        ])->assertOk();

        $this->patchJson(route('tools.ko-ki.progress.update'), [
            'last_phase' => 'activity',
            'current_level' => 2,
            'last_program' => ['MOVE_FORWARD', 'TURN_RIGHT', 'MOVE_FORWARD'],
            'increment_attempt' => true,
            'prediction' => 'unsure',
            'completed_level' => 2,
        ])->assertOk();

        $progress->refresh();

        $this->assertSame([1, 2], $progress->completed_levels);
        $this->assertSame([1 => 1, 2 => 1], $progress->level_attempts);
        $this->assertSame('unsure', $progress->predictions[2]);
        $this->assertSame(['MOVE_FORWARD', 'MOVE_FORWARD'], $progress->level_programs[1]);
        $this->assertSame(['MOVE_FORWARD', 'TURN_RIGHT', 'MOVE_FORWARD'], $progress->level_programs[2]);
        $this->assertSame(2, $progress->attempt_count);
    }

    public function test_student_cannot_finish_before_all_missions_are_complete(): void
    {
        $this->startStudent();
        $this->answerAllInitialQuestions();
        $this->post(route('tools.ko-ki.activity.start'));

        $this->post(route('tools.ko-ki.finish'), $this->validCompletionPayload())
            ->assertRedirect(route('tools.ko-ki.activity'));

        $this->assertNull(KoKiProgress::query()->sole()->completed_at);
    }

    public function test_debugging_steps_are_merged_and_saved_for_mission_three(): void
    {
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Mira',
            'grade' => 4,
            'last_phase' => 'activity',
            'current_level' => 3,
            'started_at' => now(),
        ]);

        $this->withSession([KoKiProgressService::SESSION_KEY => $progress->getKey()]);

        $this->patchJson(route('tools.ko-ki.progress.update'), [
            'last_phase' => 'activity',
            'current_level' => 3,
            'level_state' => ['ran_starter' => true],
        ])->assertOk();

        $this->patchJson(route('tools.ko-ki.progress.update'), [
            'last_phase' => 'activity',
            'current_level' => 3,
            'level_state' => ['observed_bug' => true],
        ])->assertOk();

        $this->assertSame([
            'ran_starter' => true,
            'observed_bug' => true,
        ], $progress->refresh()->level_states[3]);
    }

    public function test_laboratory_is_only_available_after_four_missions(): void
    {
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Nina',
            'grade' => 4,
            'last_phase' => 'activity',
            'completed_levels' => [1, 2, 3],
            'started_at' => now(),
        ]);

        $this->withSession([KoKiProgressService::SESSION_KEY => $progress->getKey()])
            ->get(route('tools.ko-ki.laboratory'))
            ->assertRedirect(route('tools.ko-ki.activity'));

        $progress->forceFill(['completed_levels' => [1, 2, 3, 4]])->save();

        $this->get(route('tools.ko-ki.laboratory'))
            ->assertOk()
            ->assertSee('Laboratorium Perintah Aneh')
            ->assertSee('Manusia menentukan tujuan dan membuat program.')
            ->assertSee('Komputer membantu menjalankan program.')
            ->assertSee('Program adalah kumpulan perintah untuk komputer.')
            ->assertSee('Mengapa komputer membutuhkan program?')
            ->assertSee('maxlength="200"', false);
    }

    public function test_strange_command_and_answer_are_saved_without_a_score(): void
    {
        $progress = $this->laboratoryProgress();

        $this->patchJson(route('tools.ko-ki.laboratory.update'), [
            'action' => 'try_command',
            'command' => 'BECOME_DRAGON',
        ])->assertOk()
            ->assertJsonPath('lab_state.tried_commands.0', 'BECOME_DRAGON');

        $this->patchJson(route('tools.ko-ki.laboratory.update'), [
            'action' => 'answer_question',
            'strange_answer' => 'lazy',
        ])->assertOk()
            ->assertJsonPath('result.strange_correct', false)
            ->assertJsonPath('lab_state.strange_completed', false);

        $this->patchJson(route('tools.ko-ki.laboratory.update'), [
            'action' => 'answer_question',
            'strange_answer' => 'not_created',
        ])->assertOk()
            ->assertJsonPath('result.strange_correct', true)
            ->assertJsonPath('lab_state.strange_completed', true);

        $this->assertSame('not_created', $progress->refresh()->lab_state['strange_answer']);
    }

    public function test_classification_uses_click_choices_and_persists_until_complete(): void
    {
        $progress = $this->laboratoryProgress([
            'strange_answer' => 'not_created',
            'strange_completed' => true,
        ]);

        $firstItem = KoKiLaboratoryService::CLASSIFICATIONS[0];

        $this->patchJson(route('tools.ko-ki.laboratory.update'), [
            'action' => 'classify',
            'classification_item' => $firstItem['id'],
            'classification_category' => 'computer',
        ])->assertOk()
            ->assertJsonPath('result.classification_correct', false)
            ->assertJsonPath('lab_state.classification_index', 0);

        foreach (KoKiLaboratoryService::CLASSIFICATIONS as $index => $item) {
            $this->patchJson(route('tools.ko-ki.laboratory.update'), [
                'action' => 'classify',
                'classification_item' => $item['id'],
                'classification_category' => $item['category'],
            ])->assertOk()
                ->assertJsonPath('result.classification_correct', true)
                ->assertJsonPath('lab_state.classification_index', $index + 1);
        }

        $progress->refresh();

        $this->assertTrue($progress->lab_state['classification_completed']);
        $this->assertTrue($progress->lab_state['completed']);
    }

    public function test_student_cannot_finish_before_laboratory_is_complete(): void
    {
        $progress = $this->laboratoryProgress();

        $this->post(route('tools.ko-ki.finish'), $this->validCompletionPayload())
            ->assertRedirect(route('tools.ko-ki.laboratory'));

        $this->assertNull($progress->refresh()->completed_at);
    }

    public function test_reflection_is_limited_to_two_hundred_characters(): void
    {
        $progress = $this->laboratoryProgress(['completed' => true]);

        $payload = $this->validCompletionPayload(str_repeat('a', 201));

        $this->from(route('tools.ko-ki.laboratory'))
            ->post(route('tools.ko-ki.finish'), $payload)
            ->assertRedirect(route('tools.ko-ki.laboratory'))
            ->assertSessionHasErrors(['reflection']);

        $this->assertNull($progress->refresh()->completed_at);
    }

    public function test_student_cannot_modify_another_students_progress(): void
    {
        $studentOne = KoKiProgress::query()->create([
            'student_name' => 'Ayu',
            'grade' => 4,
            'last_phase' => 'activity',
            'started_at' => now(),
        ]);
        $studentTwo = KoKiProgress::query()->create([
            'student_name' => 'Beni',
            'grade' => 5,
            'last_phase' => 'activity',
            'started_at' => now(),
        ]);

        $this->withSession([KoKiProgressService::SESSION_KEY => $studentOne->getKey()])
            ->patchJson(route('tools.ko-ki.progress.update'), [
                'progress_id' => $studentTwo->getKey(),
                'last_phase' => 'activity',
                'current_level' => 1,
                'last_program' => ['MOVE_FORWARD', 'MOVE_FORWARD'],
                'completed_level' => 1,
            ])
            ->assertOk();

        $this->assertSame([1], $studentOne->refresh()->completed_levels);
        $this->assertNull($studentTwo->refresh()->completed_levels);
        $this->assertNull($studentTwo->last_program);
    }

    public function test_activity_requires_an_active_learning_session(): void
    {
        $this->get(route('tools.ko-ki.activity'))
            ->assertRedirect(route('tools.ko-ki.welcome'));
    }

    private function startStudent(string $name = 'Bima', int $grade = 4): void
    {
        $this->post(route('tools.ko-ki.start'), [
            'student_name' => $name,
            'grade' => $grade,
        ])->assertRedirect(route('tools.ko-ki.opening'));
    }

    private function answerAllInitialQuestions(): void
    {
        $this->post(route('tools.ko-ki.questions.start'));

        foreach ([1 => 'unknown', 2 => 'no', 3 => 'program', 4 => 'programmer'] as $question => $answer) {
            $this->post(route('tools.ko-ki.questions.answer', $question), [
                'answer' => $answer,
            ]);
        }
    }

    private function laboratoryProgress(array $labState = []): KoKiProgress
    {
        $progress = KoKiProgress::query()->create([
            'student_name' => 'Lala',
            'grade' => 4,
            'last_phase' => 'laboratory',
            'completed_levels' => [1, 2, 3, 4],
            'lab_state' => $labState,
            'started_at' => now(),
        ]);

        $this->withSession([KoKiProgressService::SESSION_KEY => $progress->getKey()]);

        return $progress;
    }

    private function validCompletionPayload(string $reflection = 'Komputer membutuhkan program agar dapat mengikuti instruksi.'): array
    {
        return [
            'exit_ticket_answers' => [
                'q1' => 'not_quite',
                'q2' => 'true',
                'q3' => 'not_quite',
            ],
            'reflection' => $reflection,
            'reflection_choice' => 'Agar tahu apa yang harus dilakukan',
        ];
    }
}
