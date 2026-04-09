<?php

namespace App\Http\Controllers;

use App\Models\ProgramVersion;
use App\Services\AdminPlanManagementService;
use Illuminate\Http\Request;
use Throwable;

class AdminPlanManagementController extends Controller
{
    protected AdminPlanManagementService $adminPlanManagementService;

    public function __construct(AdminPlanManagementService $adminPlanManagementService)
    {
        $this->adminPlanManagementService = $adminPlanManagementService;
    }

    public function getPendingTrainingPlans()
    {
        try {
            $data = $this->adminPlanManagementService->getPendingTrainingPlans();
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function saveEditedTrainingPlan(Request $request)
    {
        try {
            $this->adminPlanManagementService->saveEditedTrainingPlan($request);
            return response()->json([
                'status' => true,
                'message' => 'Training plan updated successfully'
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function getPendingNutritionPlans()
    {
        try {
            $data = $this->adminPlanManagementService->getPendingNutritionPlans();
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function saveEditedNutritionPlan(Request $request)
    {
        try {
            $this->adminPlanManagementService->saveEditedNutritionPlan($request);
            return response()->json([
                'status' => true,
                'message' => 'Nutrition plan updated successfully'
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    

}
