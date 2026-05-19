<?php

namespace App\Http\Controllers;

use App\Services\CoachService;
use Throwable;

class CoachesController extends Controller
{
    private CoachService $coachService;

    public function __construct(CoachService $coachService)
    {
        $this->coachService = $coachService;
    }

    public function index()
    {
        try {
            return response()->json([
                'coaches' => $this->coachService->index(),
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
