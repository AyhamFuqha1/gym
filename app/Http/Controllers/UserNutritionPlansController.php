<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserNutritionPlanRequest;
use App\Http\Requests\UpdateUserNutritionPlanRequest;
use App\Services\UserNutritionPlansService;
use Illuminate\Http\JsonResponse;

class UserNutritionPlansController extends Controller
{
    private UserNutritionPlansService $userNutritionPlansService;

    public function __construct(UserNutritionPlansService $userNutritionPlansService)
    {
        $this->userNutritionPlansService = $userNutritionPlansService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $userNutritionPlans = $this->userNutritionPlansService->getUserNutritionPlansByUser(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $userNutritionPlans
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserNutritionPlanRequest $request): JsonResponse
    {
        $userNutritionPlan = $this->userNutritionPlansService->createUserNutritionPlan($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User nutrition plan created successfully',
            'data' => $userNutritionPlan
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $userNutritionPlan = $this->userNutritionPlansService->getUserNutritionPlan($id);

        if (!$userNutritionPlan) {
            return response()->json([
                'success' => false,
                'message' => 'User nutrition plan not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $userNutritionPlan
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserNutritionPlanRequest $request, int $id): JsonResponse
    {
        $userNutritionPlan = $this->userNutritionPlansService->updateUserNutritionPlan($id, $request->validated());

        if (!$userNutritionPlan) {
            return response()->json([
                'success' => false,
                'message' => 'User nutrition plan not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User nutrition plan updated successfully',
            'data' => $userNutritionPlan
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userNutritionPlansService->deleteUserNutritionPlan($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'User nutrition plan not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User nutrition plan deleted successfully'
        ]);
    }
}
