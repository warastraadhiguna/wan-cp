<?php

namespace Tests\Feature;

use App\Filament\Resources\QuizMeetings\Pages\ListQuizMeetings;
use App\Filament\Resources\QuizMeetings\QuizMeetingResource;
use App\Models\QuizAttempt;
use App\Models\QuizBank;
use App\Models\QuizMeeting;
use App\Models\User;
use App\Services\QuizQuestionImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_uploads_a_valid_bank_and_controls_the_active_meeting(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $first = QuizMeeting::create(['title' => 'Pertemuan Lama']);
        $second = QuizMeeting::create(['title' => 'Petualangan Robot']);
        $first->forceFill(['active_slot' => 1])->save();
        $this->createBank($first);

        $this->actingAs($admin)
            ->get(QuizMeetingResource::getUrl('upload', ['record' => $second]))
            ->assertOk()
            ->assertSee('Unduh contoh format TXT');
        $this->get(route('admin.quiz-banks.example'))->assertDownload('contoh-soal-kuis.txt');

        $upload = route('admin.quiz-banks.store', $second);
        $this->post($upload, [
            'file' => UploadedFile::fake()->createWithContent('rusak.txt', 'SOAL: Tanpa pilihan'),
            'seconds_per_question' => 15,
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('quiz_banks', 1);

        $example = file_get_contents(resource_path('quiz/contoh-soal.txt'));
        $this->post($upload, [
            'file' => UploadedFile::fake()->createWithContent('robot.txt', "\xEF\xBB\xBF".str_replace("\n", "\r\n", $example)),
            'seconds_per_question' => 15,
        ])->assertRedirect(QuizMeetingResource::getUrl('index'));

        $bank = $second->currentBank;
        $this->assertSame(15, $bank->seconds_per_question);
        $this->assertSame('B', $bank->questions[0]['answer']);

        Livewire::test(ListQuizMeetings::class)->callTableAction('activate', $second);
        $this->assertNull($first->refresh()->active_slot);
        $this->assertSame(1, (int) $second->refresh()->active_slot);
        $this->get(route('tools.quiz.index'))->assertOk()->assertSee('Petualangan Robot')->assertDontSee('Pertemuan Lama');

        $this->post($upload, [
            'file' => UploadedFile::fake()->createWithContent('robot.txt', $example),
            'seconds_per_question' => 10,
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('quiz_banks', 2);

        Livewire::test(ListQuizMeetings::class)->callTableAction('deactivate', $second->refresh());
        $this->get(route('tools.quiz.index'))->assertOk()->assertSee('Tunggu pengajar membuka pertemuan');

        $this->post($upload, [
            'file' => UploadedFile::fake()->createWithContent('robot.txt', $example),
            'seconds_per_question' => 10,
        ])->assertRedirect(QuizMeetingResource::getUrl('index'));
        $this->assertNotSame($bank->id, $second->refresh()->currentBank->id);
        $this->assertDatabaseHas('quiz_banks', ['id' => $bank->id]);
    }

    public function test_non_admin_cannot_manage_or_upload_questions(): void
    {
        $meeting = QuizMeeting::create(['title' => 'Pertemuan']);
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(QuizMeetingResource::getUrl('index'))->assertForbidden();
        $this->post(route('admin.quiz-banks.store', $meeting))->assertForbidden();
        $this->get(route('admin.quiz-banks.example'))->assertForbidden();
    }

    public function test_countdown_scoring_and_leaderboard_use_server_time_and_completed_attempts(): void
    {
        $this->freezeTime();
        $meeting = QuizMeeting::create(['title' => 'Petualangan Robot']);
        $meeting->forceFill(['active_slot' => 1])->save();
        $bank = $this->createBank($meeting);

        $this->get(route('tools.quiz.index'))->assertOk()->assertSee('Nama panggilan');
        $ownerCookie = session()->getId();
        $this->withCookie(config('session.cookie'), $ownerCookie);
        $this->post(route('tools.quiz.join'), ['bank' => $bank->id, 'nickname' => 'Kapten Naya'])->assertRedirect();
        $attempt = $bank->attempts()->sole();

        $play = route('tools.quiz.play', $attempt);
        $next = route('tools.quiz.next', $attempt);
        $answer = route('tools.quiz.answer', $attempt);
        $results = route('tools.quiz.results', $attempt);

        $this->get($play)->assertOk()->assertSee('Ayo mulai')->assertDontSee($bank->questions[0]['prompt']);
        $this->post($next, ['position' => 0])->assertRedirect($play);
        $this->get($play)->assertOk()->assertSee($bank->questions[0]['prompt'])->assertDontSee($bank->questions[1]['prompt']);
        $this->post($answer, ['position' => 0])->assertRedirect($play);
        $this->assertSame([], $attempt->refresh()->answers);
        $this->travel(2)->seconds();
        $this->post($answer, ['position' => 0, 'choice' => 'B', 'elapsed_ms' => 1])->assertRedirect($play);
        $this->get($play)->assertOk()->assertSee('Jawabanmu tepat!');
        $this->assertSame(2000, $attempt->refresh()->correct_time_ms);

        $this->post($next, ['position' => 1])->assertRedirect($play);
        $this->post($answer, ['position' => 0, 'choice' => 'D'])->assertRedirect($play);
        $this->assertCount(1, $attempt->refresh()->answers);
        $this->travel(21)->seconds();
        $this->post($answer, ['position' => 1, 'choice' => 'D'])->assertRedirect($results);
        $this->assertNull($attempt->refresh()->answers[1]['choice']);
        $this->assertSame(1, $attempt->correct_count);

        $this->addPlayer($bank, 'Akurasi Juara', 2, 9000);
        $this->addPlayer($bank, 'Kilat', 1, 1000);
        $this->addPlayer($bank, 'Santai', 1, 3000);
        $this->addPlayer($bank, 'Masih Bermain', 2, 0, false);

        $this->get($results)->assertOk()
            ->assertViewHas('rank', 3)
            ->assertViewHas('leaderboard', fn ($players): bool => $players->pluck('nickname')->all() === [
                'Akurasi Juara', 'Kilat', 'Kapten Naya', 'Santai',
            ])
            ->assertDontSee('Masih Bermain');

        $this->withCookie(config('session.cookie'), str_repeat('x', 40))
            ->get($results)->assertNotFound();
    }

    public function test_closed_meetings_reject_new_entries_and_answers(): void
    {
        $meeting = QuizMeeting::create(['title' => 'Pertemuan']);
        $meeting->forceFill(['active_slot' => 1])->save();
        $bank = $this->createBank($meeting);
        $this->get(route('tools.quiz.index'))->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post(route('tools.quiz.join'), ['bank' => $bank->id, 'nickname' => 'Naya'])->assertRedirect();
        $attempt = $bank->attempts()->sole();
        $this->post(route('tools.quiz.next', $attempt), ['position' => 0])->assertRedirect();

        $meeting->forceFill(['active_slot' => null])->save();
        $this->post(route('tools.quiz.join'), ['bank' => $bank->id, 'nickname' => 'Naya'])->assertRedirect(route('tools.quiz.index'));
        $this->get(route('tools.quiz.play', $attempt))->assertRedirect(route('tools.quiz.index'));
        $this->post(route('tools.quiz.answer', $attempt), ['position' => 0, 'choice' => 'B'])->assertRedirect(route('tools.quiz.index'));
        $this->assertSame([], $attempt->refresh()->answers);
    }

    private function createBank(QuizMeeting $meeting): QuizBank
    {
        return $meeting->banks()->create([
            'questions' => app(QuizQuestionImport::class)->parse(file_get_contents(resource_path('quiz/contoh-soal.txt'))),
            'seconds_per_question' => 20,
        ]);
    }

    private function addPlayer(QuizBank $bank, string $name, int $correct, int $time, bool $completed = true): void
    {
        $attempt = new QuizAttempt;
        $attempt->forceFill([
            'player_key' => hash('sha256', $name),
            'nickname' => $name,
            'answers' => [],
            'correct_count' => $correct,
            'correct_time_ms' => $time,
            'completed_at' => $completed ? now() : null,
        ]);
        $bank->attempts()->save($attempt);
    }
}
