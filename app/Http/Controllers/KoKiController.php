<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerKoKiQuestionRequest;
use App\Http\Requests\CompleteKoKiProgressRequest;
use App\Http\Requests\StartKoKiProgressRequest;
use App\Http\Requests\UpdateKoKiLaboratoryRequest;
use App\Http\Requests\UpdateKoKiProgressRequest;
use App\Models\KoKiProgress;
use App\Services\KoKiAdventureService;
use App\Services\KoKiLaboratoryService;
use App\Services\KoKiLessonService;
use App\Services\KoKiProgressService;
use App\Services\KoKiRescueProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KoKiController extends Controller
{
    public function __construct(
        private readonly KoKiProgressService $progressService,
        private readonly KoKiLessonService $lessonService,
        private readonly KoKiLaboratoryService $laboratoryService,
        private readonly KoKiAdventureService $adventureService,
        private readonly KoKiRescueProgressService $rescueService,
    ) {}

    public function index(Request $request): View
    {
        $meetingOneProgress = $this->progressService->currentForMeeting($request, 1);
        $meetingTwoProgress = $this->progressService->currentForMeeting($request, 2);
        $meetingTwoCompleted = $meetingTwoProgress instanceof KoKiProgress
            && $this->adventureService->state($meetingTwoProgress)['completed'];
        $meetingThreeProgress = $this->progressService->currentForMeeting($request, 3);
        $meetingThreeState = $meetingThreeProgress instanceof KoKiProgress
            ? $this->rescueService->state($meetingThreeProgress)
            : null;

        return view('tools.ko-ki.index', [
            'progress' => $meetingOneProgress,
            'resumeUrl' => $meetingOneProgress instanceof KoKiProgress ? $this->resumeUrl($meetingOneProgress) : null,
            'meetingTwoProgress' => $meetingTwoProgress,
            'meetingTwoCompleted' => $meetingTwoCompleted,
            'meetingTwoResumeUrl' => $meetingTwoProgress instanceof KoKiProgress
                ? ($this->adventureService->state($meetingTwoProgress)['intro_seen']
                    ? route('tools.ko-ki.meeting-two.map')
                    : route('tools.ko-ki.meeting-two.activity'))
                : null,
            'meetingThreeProgress' => $meetingThreeProgress,
            'meetingThreeCompleted' => (bool) ($meetingThreeState['evaluation_passed'] ?? false),
            'meetingThreeResumeUrl' => $meetingThreeProgress instanceof KoKiProgress
                ? (($meetingThreeState['final_completed'] ?? false)
                    ? route('tools.ko-ki.meeting-three.evaluation')
                    : (($meetingThreeState['intro_seen'] ?? false)
                        ? route('tools.ko-ki.meeting-three.map')
                        : route('tools.ko-ki.meeting-three.intro')))
                : null,
        ]);
    }

    public function welcome(Request $request): View
    {
        return view('tools.ko-ki.welcome', [
            'progress' => $progress = $this->progressService->current($request),
            'resumeUrl' => $progress instanceof KoKiProgress ? $this->resumeUrl($progress) : null,
        ]);
    }

    public function start(StartKoKiProgressRequest $request): RedirectResponse
    {
        $this->progressService->start($request, $request->validated());

        return redirect()->route('tools.ko-ki.opening');
    }

    public function opening(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        if ($progress->last_phase !== 'opening') {
            return redirect()->to($this->resumeUrl($progress));
        }

        return view('tools.ko-ki.opening', compact('progress'));
    }

    public function beginQuestions(Request $request): RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        $nextQuestion = $this->lessonService->nextQuestionNumber($progress->initial_answer);

        if ($nextQuestion === null) {
            return redirect()->route('tools.ko-ki.questions.completed');
        }

        $this->progressService->setPhase($progress, 'question_'.$nextQuestion);

        return redirect()->route('tools.ko-ki.questions.show', $nextQuestion);
    }

    public function question(Request $request, int $question): View|RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        $nextQuestion = $this->lessonService->nextQuestionNumber($progress->initial_answer);

        if ($nextQuestion === null) {
            return redirect()->route('tools.ko-ki.questions.completed');
        }

        if ($question !== $nextQuestion) {
            return redirect()->route('tools.ko-ki.questions.show', $nextQuestion);
        }

        $questionData = $this->lessonService->question($question);
        abort_if($questionData === null, 404);

        return view('tools.ko-ki.question', [
            'progress' => $progress,
            'questionNumber' => $question,
            'question' => $questionData,
            'questionCount' => count($this->lessonService->questions()),
        ]);
    }

    public function answer(AnswerKoKiQuestionRequest $request, int $question): RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        $nextQuestion = $this->lessonService->nextQuestionNumber($progress->initial_answer);

        if ($nextQuestion === null) {
            return redirect()->route('tools.ko-ki.questions.completed');
        }

        if ($question !== $nextQuestion) {
            return redirect()->route('tools.ko-ki.questions.show', $nextQuestion);
        }

        $this->progressService->saveInitialAnswer(
            $progress,
            $question,
            $request->validated('answer'),
        );

        if ($question === count($this->lessonService->questions())) {
            return redirect()->route('tools.ko-ki.questions.completed');
        }

        return redirect()->route('tools.ko-ki.questions.show', $question + 1);
    }

    public function questionsCompleted(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        $nextQuestion = $this->lessonService->nextQuestionNumber($progress->initial_answer);

        if ($nextQuestion !== null) {
            return redirect()->route('tools.ko-ki.questions.show', $nextQuestion);
        }

        return view('tools.ko-ki.questions-completed', compact('progress'));
    }

    public function beginActivity(Request $request): RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        if ($this->lessonService->nextQuestionNumber($progress->initial_answer) !== null) {
            return redirect()->to($this->resumeUrl($progress));
        }

        $this->progressService->setPhase($progress, 'activity');

        return redirect()->route('tools.ko-ki.activity');
    }

    public function activity(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        if ($progress->completed_at !== null) {
            return redirect()->route('tools.ko-ki.completed');
        }

        if ($progress->last_phase !== 'activity') {
            return redirect()->to($this->resumeUrl($progress));
        }

        return view('tools.ko-ki.activity', [
            'progress' => $progress,
            'approvedLevels' => $this->progressService->approvedLevels($progress),
        ]);
    }

    public function update(UpdateKoKiProgressRequest $request): JsonResponse|RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Session belajar tidak ditemukan.'], 409)
                : redirect()->route('tools.ko-ki.welcome');
        }

        $data = $request->validated();

        if (! $this->progressService->canAccessLevel($progress, (int) $data['current_level'])) {
            return response()->json([
                'message' => 'Misi ini masih dikunci. Minta izin admin terlebih dahulu.',
            ], 403);
        }

        $progress = $this->progressService->update($progress, $data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Progress berhasil disimpan.',
                'progress' => $progress,
            ]);
        }

        return redirect()
            ->route('tools.ko-ki.activity')
            ->with('status', 'Progress berhasil disimpan.');
    }

    public function levelStatus(Request $request): JsonResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return response()->json(['message' => 'Session belajar tidak ditemukan.'], 409);
        }

        return response()->json([
            'current_level' => $progress->current_level,
            'approved_levels' => $this->progressService->approvedLevels($progress),
        ]);
    }

    public function finish(CompleteKoKiProgressRequest $request): RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return redirect()->route('tools.ko-ki.welcome');
        }

        if (! in_array(KoKiProgressService::MISSION_COUNT, $progress->completed_levels ?? [], true)) {
            return redirect()
                ->route('tools.ko-ki.activity')
                ->with('status', 'Selesaikan tujuh misi sebelum mengakhiri pertemuan.');
        }

        if (! ($progress->lab_state['completed'] ?? false)) {
            return redirect()
                ->route('tools.ko-ki.laboratory')
                ->with('status', 'Selesaikan Laboratorium Perintah Aneh terlebih dahulu.');
        }

        $this->progressService->complete($progress, $request->validated());

        return redirect()->route('tools.ko-ki.completed');
    }

    public function laboratory(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        if ($progress->completed_at !== null) {
            return redirect()->route('tools.ko-ki.completed');
        }

        if (! in_array(KoKiProgressService::MISSION_COUNT, $progress->completed_levels ?? [], true)) {
            return redirect()
                ->route('tools.ko-ki.activity')
                ->with('status', 'Selesaikan tujuh misi sebelum membuka laboratorium.');
        }

        if ($progress->last_phase !== 'laboratory') {
            $progress = $this->progressService->setPhase($progress, 'laboratory');
        }

        return view('tools.ko-ki.laboratory', [
            'progress' => $progress,
            'laboratory' => $this->laboratoryService->clientConfiguration(),
            'labState' => $this->laboratoryService->state($progress),
        ]);
    }

    public function updateLaboratory(UpdateKoKiLaboratoryRequest $request): JsonResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return response()->json(['message' => 'Session belajar tidak ditemukan.'], 409);
        }

        if (! in_array(KoKiProgressService::MISSION_COUNT, $progress->completed_levels ?? [], true)) {
            return response()->json(['message' => 'Tujuh misi belum selesai.'], 409);
        }

        $data = $request->validated();
        $progress = $this->laboratoryService->update($progress, $data);

        return response()->json([
            'message' => 'Progress laboratorium berhasil disimpan.',
            'lab_state' => $progress->lab_state,
            'result' => [
                'strange_correct' => ($data['strange_answer'] ?? null) === KoKiLaboratoryService::CORRECT_STRANGE_ANSWER,
                'classification_correct' => isset($data['classification_item'], $data['classification_category'])
                    && $this->laboratoryService->correctCategory($data['classification_item']) === $data['classification_category'],
            ],
        ]);
    }

    public function completed(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->current($request);

        if (! $progress instanceof KoKiProgress) {
            return redirect()->route('tools.ko-ki.welcome');
        }

        if ($progress->completed_at === null) {
            return redirect()->route('tools.ko-ki.activity');
        }

        return view('tools.ko-ki.completed', compact('progress'));
    }

    private function resumeUrl(KoKiProgress $progress): string
    {
        if ($progress->completed_at !== null) {
            return route('tools.ko-ki.completed');
        }

        if ($progress->last_phase === 'activity') {
            return route('tools.ko-ki.activity');
        }

        if ($progress->last_phase === 'laboratory') {
            return route('tools.ko-ki.laboratory');
        }

        if ($progress->last_phase === 'questions_completed') {
            return route('tools.ko-ki.questions.completed');
        }

        if (str_starts_with($progress->last_phase, 'question_')) {
            $question = (int) str($progress->last_phase)->after('question_')->toString();

            return route('tools.ko-ki.questions.show', $question);
        }

        return route('tools.ko-ki.opening');
    }

    private function missingSessionRedirect(): RedirectResponse
    {
        return redirect()
            ->route('tools.ko-ki.welcome')
            ->with('status', 'Isi nama panggilan dan kelas terlebih dahulu.');
    }
}
