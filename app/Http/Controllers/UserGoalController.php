<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserGoalRequest;
use App\Http\Requests\UpdateUserGoalRequest;
use App\Services\UserGoalService;
use Illuminate\Http\Request;
use Throwable;

class UserGoalController extends Controller
{
    public UserGoalService $userGoalService;

    public function __construct(UserGoalService $userGoalService)
    {
        $this->userGoalService = $userGoalService;
    }

    public function index()
    {
        try {
            $goals = $this->userGoalService->index();
            return response()->json($goals, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreUserGoalRequest $request)
    {
        try {
            $data = $request->validated();
            $goal = $this->userGoalService->store($data);
            return response()->json($goal, 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $goal = $this->userGoalService->show($id);
            return response()->json($goal, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateUserGoalRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $goal = $this->userGoalService->update($id, $data);
            return response()->json($goal, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->userGoalService->destroy($id);
            return response()->json(['message' => 'Goal deleted'], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
