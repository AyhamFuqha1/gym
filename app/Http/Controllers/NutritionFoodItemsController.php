<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNutritionFoodItemRequest;
use App\Http\Requests\UpdateNutritionFoodItemRequest;
use App\Services\NutritionFoodItemsService;
use Illuminate\Http\JsonResponse;

class NutritionFoodItemsController extends Controller
{
    private NutritionFoodItemsService $nutritionFoodItemsService;

    public function __construct(NutritionFoodItemsService $nutritionFoodItemsService)
    {
        $this->nutritionFoodItemsService = $nutritionFoodItemsService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // This might need to be filtered by version or user
        $nutritionFoodItems = $this->nutritionFoodItemsService->getAllNutritionFoodItems();

        return response()->json([
            'success' => true,
            'data' => $nutritionFoodItems
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNutritionFoodItemRequest $request): JsonResponse
    {
        $nutritionFoodItem = $this->nutritionFoodItemsService->createNutritionFoodItem($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Nutrition food item created successfully',
            'data' => $nutritionFoodItem
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $nutritionFoodItem = $this->nutritionFoodItemsService->getNutritionFoodItem($id);

        if (!$nutritionFoodItem) {
            return response()->json([
                'success' => false,
                'message' => 'Nutrition food item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $nutritionFoodItem
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNutritionFoodItemRequest $request, int $id): JsonResponse
    {
        $nutritionFoodItem = $this->nutritionFoodItemsService->updateNutritionFoodItem($id, $request->validated());

        if (!$nutritionFoodItem) {
            return response()->json([
                'success' => false,
                'message' => 'Nutrition food item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nutrition food item updated successfully',
            'data' => $nutritionFoodItem
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->nutritionFoodItemsService->deleteNutritionFoodItem($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Nutrition food item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nutrition food item deleted successfully'
        ]);
    }
}
