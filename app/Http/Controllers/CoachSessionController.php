<?php

namespace App\Http\Controllers;

use App\Services\CoachSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachSessionController extends Controller
{
    public CoachSessionService $coachSessionService;

    public function __construct(CoachSessionService $coachSessionService)
    {
        $this->coachSessionService = $coachSessionService;
    }

    public function getCoaches(): JsonResponse
    {
        return response()->json($this->coachSessionService->getCoaches());
    }

    public function store(Request $request): JsonResponse
    {
        $session = $this->coachSessionService->store($this->sessionData($request));

        return response()->json($session, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->coachSessionService->show($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $session = $this->coachSessionService->update($this->sessionData($request, true), $id);

        return response()->json($session);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->coachSessionService->destroy($id);

        return response()->json([
            'message' => 'Coach session deleted successfully',
        ]);
    }

    public function cancelCoachSession(int $id): JsonResponse
    {
        $session = $this->coachSessionService->cancelWholeSession($id);

        return response()->json([
            'message' => 'Session cancelled successfully',
            'data' => $session,
        ]);
    }

    public function showSessions(): JsonResponse
    {
        return response()->json($this->coachSessionService->showSessions());
    }

    public function getMySessions(): JsonResponse
    {
        return response()->json($this->coachSessionService->getMySessions());
    }

    public function bookSession(int $id): JsonResponse
    {
        $session = $this->coachSessionService->bookSession($id);

        return response()->json([
            'message' => 'Session booked successfully',
            'data' => $session,
        ]);
    }

    public function cancelSession(int $id): JsonResponse
    {
        $session = $this->coachSessionService->cancelSession($id);

        return response()->json([
            'message' => 'Session cancelled successfully',
            'data' => $session,
        ]);
    }

    public function getAllSessionsForAdmin(): JsonResponse
    {
        return response()->json($this->coachSessionService->getAllSessionsForAdmin());
    }

    public function getSessionDetailsForAdmin(int $sessionId): JsonResponse
    {
        return response()->json($this->coachSessionService->getSessionDetailsForAdmin($sessionId));
    }

    public function adminCancelSession(int $id): JsonResponse
    {
        $session = $this->coachSessionService->adminCancelSession($id);

        return response()->json([
            'message' => 'Session cancelled successfully',
            'data' => $session,
        ]);
    }

    public function adminRestoreSession(int $id): JsonResponse
    {
        $session = $this->coachSessionService->adminRestoreSession($id);

        return response()->json([
            'message' => 'Session restored successfully',
            'data' => $session,
        ]);
    }

    private function sessionData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'coach_id' => [$required, 'integer', 'exists:users,id'],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'session_date' => ['nullable', 'date'],
            'start_time' => [$required, 'date_format:H:i'],
            'end_time' => [$required, 'date_format:H:i', 'after:start_time'],
            'capacity' => [$required, 'integer', 'min:1'],
            'booked_count' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:available,full,cancelled'],
            'is_recurring' => ['sometimes', 'boolean'],
        ]);
    }
}
