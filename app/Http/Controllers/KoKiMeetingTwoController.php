<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartKoKiMeetingTwoRequest;
use App\Http\Requests\UpdateKoKiAdventureProgressRequest;
use App\Models\KoKiProgress;
use App\Services\KoKiAdventureService;
use App\Services\KoKiProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KoKiMeetingTwoController extends Controller
{
    public function __construct(
        private readonly KoKiProgressService $progressService,
        private readonly KoKiAdventureService $adventureService,
    ) {}

    public function welcome(Request $request): View
    {
        $progress = $this->progressService->currentForMeeting($request, 2);

        return view('tools.ko-ki.meeting-two.welcome', [
            'progress' => $progress,
            'resumeUrl' => $progress instanceof KoKiProgress ? $this->resumeUrl($progress) : null,
        ]);
    }

    public function start(StartKoKiMeetingTwoRequest $request): RedirectResponse
    {
        $this->progressService->startMeeting($request, $request->validated(), 2, 'factory_intro');

        return redirect()->route('tools.ko-ki.meeting-two.activity');
    }

    public function activity(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 2);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        $state = $this->adventureService->state($progress);
        if ($state['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-two.map');
        }

        return view('tools.ko-ki.meeting-two.intro', compact('progress'));
    }

    public function map(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 2);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        $state = $this->adventureService->state($progress);
        if (! $state['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-two.activity');
        }
        if (in_array(1, $state['completed_levels'], true) && ! $state['training_replay_seen']) {
            return redirect()->route('tools.ko-ki.meeting-two.mission', 'level-1');
        }

        return view('tools.ko-ki.meeting-two.map', [
            'progress' => $progress,
            'factoryState' => $state,
            'factory' => $this->adventureService->configuration(),
        ]);
    }

    public function mission(Request $request, string $location): View|RedirectResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 2);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        if (! $this->adventureService->state($progress)['intro_seen']) {
            return redirect()->route('tools.ko-ki.meeting-two.activity');
        }

        abort_unless(preg_match('/^level-([1-5])$/', $location, $matches) === 1, 404);
        $level = (int) $matches[1];
        $state = $this->adventureService->state($progress);
        if (! $this->adventureService->isUnlocked($state, $level)) {
            return redirect()->route('tools.ko-ki.meeting-two.map')->with('status', 'Level itu masih terkunci.');
        }

        return view('tools.ko-ki.meeting-two.factory', [
            'progress' => $progress,
            'factoryState' => $state,
            'factory' => $this->adventureService->configuration(),
            'level' => $level,
            'levelDefinition' => KoKiAdventureService::LEVELS[$level],
        ]);
    }

    public function update(UpdateKoKiAdventureProgressRequest $request): JsonResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 2);

        if (! $progress instanceof KoKiProgress) {
            return response()->json(['message' => 'Session Pertemuan 2 tidak ditemukan.'], 409);
        }

        $data = $request->validated();
        if ($data['action'] === 'complete_intro') {
            $progress = $this->adventureService->completeIntro($progress);
            $result = [];
        } elseif ($data['action'] === 'factory_training_replay') {
            $progress = $this->adventureService->acknowledgeTrainingReplay($progress);
            $result = ['training_replay_seen' => true];
        } elseif (in_array($data['action'], ['factory_exit_identify', 'factory_exit_arrange'], true)) {
            $played = $this->adventureService->submitExitChallenge($progress, $data);
            $progress = $played['progress'];
            $result = $played['result'];
        } elseif ($data['action'] === 'factory_start_level') {
            $progress = $this->adventureService->startLevel($progress, (int) $data['level']);
            $result = ['level_started' => true];
        } else {
            $played = $this->adventureService->play($progress, $data);
            $progress = $played['progress'];
            $result = $played['result'];
        }

        return response()->json([
            'message' => 'Progress Factory Rush berhasil disimpan.',
            'factory_state' => $state = $this->adventureService->state($progress),
            'result' => [
                ...$result,
                'total_score' => $state['score'],
                'best_combo' => $state['best_combo'],
                'order_count' => $state['order_count'],
                'map_url' => route('tools.ko-ki.meeting-two.map'),
            ],
        ]);
    }

    public function completed(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 2);

        if (! $progress instanceof KoKiProgress) {
            return $this->missingSessionRedirect();
        }

        $state = $this->adventureService->state($progress);
        if (! $state['factory_finished']) {
            return redirect()->route('tools.ko-ki.meeting-two.map');
        }

        return view('tools.ko-ki.meeting-two.finale', [
            'progress' => $progress,
            'factoryState' => $state,
            'factory' => $this->adventureService->configuration(),
        ]);
    }

    private function resumeUrl(KoKiProgress $progress): string
    {
        $state = $this->adventureService->state($progress);
        if ($state['factory_finished']) {
            return route('tools.ko-ki.meeting-two.completed');
        }

        return $state['intro_seen'] ? route('tools.ko-ki.meeting-two.map') : route('tools.ko-ki.meeting-two.activity');
    }

    private function missingSessionRedirect(): RedirectResponse
    {
        return redirect()
            ->route('tools.ko-ki.meeting-two.welcome')
            ->with('status', 'Isi nama panggilan dan kelas terlebih dahulu.');
    }
}
