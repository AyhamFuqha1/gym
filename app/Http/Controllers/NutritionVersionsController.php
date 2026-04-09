<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNutritionVersionRequest;
use App\Http\Requests\UpdateNutritionVersionRequest;
use App\Services\NutritionVersionsService;
use Illuminate\Http\JsonResponse;

class NutritionVersionsController extends Controller
{
    private NutritionVersionsService $nutritionVersionsService;

    public function __construct(NutritionVersionsService $nutritionVersionsService)
    {
        $this->nutritionVersionsService = $nutritionVersionsService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $nutritionVersions = $this->nutritionVersionsService->getAllNutritionVersions();

        return response()->json([
            'success' => true,
            'data' => $nutritionVersions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNutritionVersionRequest $request): JsonResponse
    {
        $nutritionVersion = $this->nutritionVersionsService->createNutritionVersion($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Nutrition version created successfully',
            'data' => $nutritionVersion
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $nutritionVersion = $this->nutritionVersionsService->getNutritionVersion($id);

        if (!$nutritionVersion) {
            return response()->json([
                'success' => false,
                'message' => 'Nutrition version not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $nutritionVersion
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNutritionVersionRequest $request, int $id): JsonResponse
    {
        $nutritionVersion = $this->nutritionVersionsService->updateNutritionVersion($id, $request->validated());

        if (!$nutritionVersion) {
            return response()->json([
                'success' => false,
                'message' => 'Nutrition version not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nutrition version updated successfully',
            'data' => $nutritionVersion
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->nutritionVersionsService->deleteNutritionVersion($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Nutrition version not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nutrition version deleted successfully'
        ]);
    }
}
