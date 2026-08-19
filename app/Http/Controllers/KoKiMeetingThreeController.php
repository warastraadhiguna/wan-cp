<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartKoKiMeetingThreeRequest;
use App\Http\Requests\SubmitKoKiRescueEvaluationRequest;
use App\Http\Requests\UpdateKoKiRescueProgressRequest;
use App\Models\KoKiProgress;
use App\Services\KoKiProgressService;
use App\Services\KoKiRescueProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KoKiMeetingThreeController extends Controller
{
    public function __construct(
        private readonly KoKiProgressService $progressService,
        private readonly KoKiRescueProgressService $rescueService,
    ) {}

    public function welcome(Request $request): View
    {
        $progress = $this->progressService->currentForMeeting($request, 3);

        return view('tools.ko-ki.meeting-three.welcome', [
            'progress' => $progress,
            'resumeUrl' => $progress instanceof KoKiProgress ? $this->resumeUrl($progress) : null,
        ]);
    }

    public function start(StartKoKiMeetingThreeRequest $request): RedirectResponse
    {
        $this->progressService->startMeeting($request, $request->validated(), 3, 'rescue_intro');

        return redirect()->route('tools.ko-ki.meeting-three.intro');
    }

    public function intro(Request $request): View|RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        if ($this->rescueService->state($progress)['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-three.map');
        }

        return view('tools.ko-ki.meeting-three.intro', compact('progress'));
    }

    public function completeIntro(Request $request): RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $this->rescueService->completeIntro($progress);

        return redirect()->route('tools.ko-ki.meeting-three.map');
    }

    public function map(Request $request): View|RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $state = $this->rescueService->state($progress);
        if (! $state['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-three.intro');
        }

        return view('tools.ko-ki.meeting-three.map', ['progress' => $progress, 'rescueState' => $state]);
    }

    public function mission(Request $request, int $mission): View|RedirectResponse
    {
        abort_unless($mission >= 1 && $mission <= 6, 404);
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        if (! $this->rescueService->state($progress)['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-three.intro');
        }
        if (! $this->rescueService->canAccessLevel($progress, $mission)) {
            return redirect()->route('tools.ko-ki.meeting-three.map')->with('status', 'Level ini masih menunggu izin admin.');
        }

        return view('tools.ko-ki.meeting-three.rescue', [
            'mission' => $mission,
            'progress' => $progress,
            'rescueState' => $this->rescueService->state($progress),
        ]);
    }

    public function completeLevel(UpdateKoKiRescueProgressRequest $request): JsonResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return response()->json(['message' => 'Session Pertemuan 3 tidak ditemukan.'], 409);
        }
        $completed = $this->rescueService->completeLevel($progress, $request->validated());

        return response()->json([
            'message' => 'Misi berhasil disimpan.',
            'stars' => $completed['stars'],
            'prediction_correct' => $completed['prediction_correct'],
            'state' => $this->rescueService->state($completed['progress']),
            'map_url' => route('tools.ko-ki.meeting-three.map'),
            'evaluation_url' => route('tools.ko-ki.meeting-three.evaluation'),
        ]);
    }

    public function evaluation(Request $request): View|RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $state = $this->rescueService->state($progress);
        if (! $state['final_completed']) {
            return redirect()->route('tools.ko-ki.meeting-three.map');
        }

        return view('tools.ko-ki.meeting-three.evaluation', ['progress' => $progress, 'rescueState' => $state]);
    }

    public function submitEvaluation(SubmitKoKiRescueEvaluationRequest $request): RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $result = $this->rescueService->submitEvaluation($progress, $request->validated());

        return redirect()->route('tools.ko-ki.meeting-three.evaluation')
            ->with($result['passed'] ? 'success' : 'error', $result['passed']
                ? 'Evaluasi berhasil. Medali sudah menjadi milikmu!'
                : 'Belum lolos. Periksa kembali tiga jawaban lalu coba lagi.');
    }

    private function current(Request $request): ?KoKiProgress
    {
        return $this->progressService->currentForMeeting($request, 3);
    }

    private function resumeUrl(KoKiProgress $progress): string
    {
        $state = $this->rescueService->state($progress);
        if ($state['final_completed']) {
            return route('tools.ko-ki.meeting-three.evaluation');
        }

        return $state['intro_seen']
            ? route('tools.ko-ki.meeting-three.map')
            : route('tools.ko-ki.meeting-three.intro');
    }

    private function missingSessionRedirect(): RedirectResponse
    {
        return redirect()->route('tools.ko-ki.meeting-three.welcome')
            ->with('status', 'Isi nama panggilan dan kelas terlebih dahulu.');
    }
}
