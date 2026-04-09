<?php

namespace App\Http\Controllers;

use App\Services\AIRequestModifcationService;
use Illuminate\Http\Request;

class AIRequestModifcationController extends Controller
{
    protected $aiRequestModificationService;

    public function __construct(AIRequestModifcationService  $aiRequestModificationService)
    {
        $this->aiRequestModificationService = $aiRequestModificationService;
    }
    public function getTrainingModificationRequests()
    {
        try{
            $requests = $this->aiRequestModificationService->getTrainingModificationRequests();
            return response()->json(['success' => true, 'data' => $requests], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getNutritionModificationRequests()
    {
        try{
            $requests = $this->aiRequestModificationService->getNutritionModificationRequests();
            return response()->json(['success' => true, 'data' => $requests], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveTraining($id)
    {
        try{
            $this->aiRequestModificationService->approveTraining($id);
            return response()->json(['success' => true, 'message' => 'Training modification approved successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveNutrition($id)
    {
        try{
            $this->aiRequestModificationService->approveNutrition($id);
            return response()->json(['success' => true, 'message' => 'Nutrition modification approved successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
