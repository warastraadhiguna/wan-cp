<?php

namespace App\Http\Controllers;

use App\Http\Requests\BeginKoKiDetectiveRequest;
use App\Http\Requests\CompleteKoKiDetectiveCaseRequest;
use App\Http\Requests\StartKoKiMeetingFourRequest;
use App\Http\Requests\SubmitKoKiDetectiveNotebookRequest;
use App\Http\Requests\SyncKoKiDetectiveProgressRequest;
use App\Models\KoKiProgress;
use App\Services\KoKiDetectiveProgressService;
use App\Services\KoKiProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KoKiMeetingFourController extends Controller
{
    public function __construct(
        private readonly KoKiProgressService $progressService,
        private readonly KoKiDetectiveProgressService $detectiveService,
    ) {}

    public function welcome(Request $request): View
    {
        $progress = $this->current($request);

        return view('tools.ko-ki.meeting-four.welcome', [
            'progress' => $progress,
            'resumeUrl' => $progress ? $this->resumeUrl($progress) : null,
        ]);
    }

    public function start(StartKoKiMeetingFourRequest $request): RedirectResponse
    {
        $this->progressService->startMeeting($request, $request->validated(), 4, 'detective_intro');

        return redirect()->route('tools.ko-ki.meeting-four.intro');
    }

    public function intro(Request $request): View|RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        if ($this->detectiveService->state($progress)['intro_seen']) {
            return redirect()->to($this->resumeUrl($progress));
        }

        return view('tools.ko-ki.meeting-four.intro', compact('progress'));
    }

    public function begin(BeginKoKiDetectiveRequest $request): RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $this->detectiveService->begin($progress, $request->validated('answer'));

        return redirect()->route('tools.ko-ki.meeting-four.case', 1);
    }

    public function map(Request $request): View|RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $state = $this->detectiveService->state($progress);
        if (! $state['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-four.intro');
        }

        return view('tools.ko-ki.meeting-four.map', ['progress' => $progress, 'detectiveState' => $state]);
    }

    public function case(Request $request, int $case): View|RedirectResponse
    {
        abort_unless($case >= 1 && $case <= KoKiDetectiveProgressService::CASE_COUNT, 404);
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        if (! $this->detectiveService->state($progress)['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-four.intro');
        }
        if (! $this->detectiveService->canAccessCase($progress, $case)) {
            return redirect()->route('tools.ko-ki.meeting-four.map')->with('status', 'Case ini masih menunggu izin admin.');
        }

        return view('tools.ko-ki.meeting-four.case', [
            'caseNumber' => $case,
            'progress' => $progress,
            'detectiveState' => $this->detectiveService->state($progress),
        ]);
    }

    public function complete(CompleteKoKiDetectiveCaseRequest $request): JsonResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return response()->json(['message' => 'Session Pertemuan 4 tidak ditemukan.'], 409);
        }
        $progress = $this->detectiveService->completeCase($progress, $request->validated());
        $state = $this->detectiveService->state($progress);

        return response()->json([
            'message' => 'CASE SOLVED! Bug Collection diperbarui.',
            'state' => $state,
            'map_url' => route('tools.ko-ki.meeting-four.map'),
            'next_url' => $state['final_solved']
                ? route('tools.ko-ki.meeting-four.notebook')
                : route('tools.ko-ki.meeting-four.case', min(KoKiDetectiveProgressService::FINAL_CASE, (int) $request->validated('case') + 1)),
        ]);
    }

    public function notebook(Request $request): View|RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $state = $this->detectiveService->state($progress);

        return view('tools.ko-ki.meeting-four.notebook', ['progress' => $progress, 'detectiveState' => $state]);
    }

    public function sync(SyncKoKiDetectiveProgressRequest $request): JsonResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return response()->json(['message' => 'Session Pertemuan 4 tidak ditemukan.'], 409);
        }
        $progress = $this->detectiveService->sync($progress, $request->validated());

        return response()->json(['message' => 'Progress tersinkron.', 'state' => $this->detectiveService->state($progress)]);
    }

    public function completeNotebook(SubmitKoKiDetectiveNotebookRequest $request): RedirectResponse
    {
        $progress = $this->current($request);
        if (! $progress) {
            return $this->missingSessionRedirect();
        }
        $result = $this->detectiveService->completeNotebook($progress, $request->validated('fix'));

        return redirect()->route('tools.ko-ki.meeting-four.notebook')->with(
            $result['passed'] ? 'success' : 'error',
            $result['passed'] ? 'Mini challenge selesai. Badge Bug Detective terbuka!' : 'Program masih berbelok ke arah yang salah. Coba perbaiki lagi.',
        );
    }

    private function current(Request $request): ?KoKiProgress
    {
        return $this->progressService->currentForMeeting($request, 4);
    }

    private function resumeUrl(KoKiProgress $progress): string
    {
        $state = $this->detectiveService->state($progress);
        if (! $state['intro_seen']) {
            return route('tools.ko-ki.meeting-four.intro');
        }

        return $state['final_solved']
            ? route('tools.ko-ki.meeting-four.notebook')
            : route('tools.ko-ki.meeting-four.case', $state['current_case']);
    }

    private function missingSessionRedirect(): RedirectResponse
    {
        return redirect()->route('tools.ko-ki.meeting-four.welcome')
            ->with('status', 'Isi nama panggilan dan kelas terlebih dahulu.');
    }
}
