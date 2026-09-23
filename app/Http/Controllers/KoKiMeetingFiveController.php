<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveKoKiCreatorRequest;
use App\Http\Requests\StartKoKiMeetingFiveRequest;
use App\Services\KoKiCreatorService;
use App\Services\KoKiProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KoKiMeetingFiveController extends Controller
{
    public function __construct(
        private readonly KoKiProgressService $progressService,
        private readonly KoKiCreatorService $creatorService,
    ) {}

    public function welcome(Request $request): View
    {
        return view('tools.ko-ki.meeting-five.welcome', [
            'progress' => $this->progressService->currentForMeeting($request, 5),
        ]);
    }

    public function start(StartKoKiMeetingFiveRequest $request): RedirectResponse
    {
        $this->progressService->startMeeting($request, $request->validated(), 5, 'creator_lab');

        return redirect()->route('tools.ko-ki.meeting-five.lab');
    }

    public function lab(Request $request): View|RedirectResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 5);
        if (! $progress) {
            return redirect()->route('tools.ko-ki.meeting-five.welcome')
                ->with('status', 'Isi nama panggilan dan kelas terlebih dahulu.');
        }

        return view('tools.ko-ki.meeting-five.lab', [
            'progress' => $progress,
            'creatorState' => $this->creatorService->state($progress),
            'creatorAdventure' => $this->creatorService->adventureState($progress),
            'creatorApprovals' => $this->creatorService->approvedSteps($progress),
            'playtestApproved' => $this->creatorService->playtestApproved($progress),
        ]);
    }

    public function save(SaveKoKiCreatorRequest $request): JsonResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 5);
        if (! $progress) {
            return response()->json(['message' => 'Session Pertemuan 5 tidak ditemukan.'], 409);
        }

        $data = $request->validated();
        if (! $this->creatorService->canCompleteSetup($progress, (int) $data['stage'])) {
            return response()->json(['message' => 'Setup berikutnya masih menunggu izin admin.'], 403);
        }
        if (($data['playtested'] ?? false) && ! $this->creatorService->playtestApproved($progress)) {
            return response()->json(['message' => 'Playtest masih menunggu izin admin.'], 403);
        }

        $progress = $this->creatorService->save($progress, $data);

        return response()->json([
            'message' => 'Karya tersimpan.',
            'state' => $this->creatorService->state($progress),
        ]);
    }

    public function playtestStatus(Request $request): JsonResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 5);
        if (! $progress) {
            return response()->json(['message' => 'Session Pertemuan 5 tidak ditemukan.'], 409);
        }

        return response()->json([
            'approved' => $this->creatorService->playtestApproved($progress),
            'approved_steps' => $this->creatorService->approvedSteps($progress),
            'next_step' => $this->creatorService->nextApprovableStep($progress),
            'setup_completed' => (int) $this->creatorService->state($progress)['stage'] >= 4,
        ]);
    }

    public function completeAdventure(Request $request): JsonResponse
    {
        $progress = $this->progressService->currentForMeeting($request, 5);
        if (! $progress) {
            return response()->json(['message' => 'Session Pertemuan 5 tidak ditemukan.'], 409);
        }

        if ((int) $this->creatorService->state($progress)['stage'] < 4) {
            return response()->json(['message' => 'Selesaikan empat tantangan Creator Lab terlebih dahulu.'], 422);
        }

        if (! $this->creatorService->playtestApproved($progress)) {
            return response()->json(['message' => 'Playtest masih menunggu izin admin.'], 403);
        }

        $data = $request->validate([
            'completed_levels' => ['required', 'array', 'size:10'],
            'completed_levels.*' => ['required', 'integer', 'between:1,10'],
            'answers' => ['required', 'array', 'size:2'],
            'answers.creator' => ['required', Rule::in(['mengatur_game'])],
            'answers.debug' => ['required', Rule::in(['uji_perbaiki'])],
        ]);

        if (array_values(array_unique(array_map('intval', $data['completed_levels']))) !== range(1, 10)) {
            return response()->json(['message' => 'Selesaikan seluruh level terlebih dahulu.'], 422);
        }

        $progress = $this->creatorService->completeAdventure($progress, $data);

        return response()->json([
            'message' => 'Medali Ko-Ki Game Creator terbuka.',
            'adventure' => $this->creatorService->adventureState($progress),
        ]);
    }
}
