<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeneralNutritionRequest;
use App\Http\Requests\UpdateGeneralNutritionRequest;
use App\Models\GeneralNutrition;
use App\Services\GeneralNutritionService;
use Illuminate\Http\Request;
use Throwable;

class GeneralNutritionController extends Controller
{
    public GeneralNutritionService $generalNutritionService;

    public function __construct(GeneralNutritionService $generalNutritionService)
    {
        $this->generalNutritionService = $generalNutritionService;
    }

    public function index()
    {
        try {
            $res = $this->generalNutritionService->index();
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function store(StoreGeneralNutritionRequest $request)
    {
        try {
            $data = $request->validated();
            $res = $this->generalNutritionService->store($data);
            return response()->json($res, 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $res = $this->generalNutritionService->show($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function update(UpdateGeneralNutritionRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $res = $this->generalNutritionService->update($id, $data);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $res = $this->generalNutritionService->destroy($id);
            if (isset($res['status']) && $res['status'] === 'failed') {
                return response()->json($res, 400);
            }
            return response()->json($res, 200);
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
