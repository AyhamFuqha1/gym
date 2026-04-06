<?php

namespace App\Services;

use App\Models\NutritionFoodItems;

class NutritionFoodItemsService
{
    /**
     * Create nutrition food item
     */
    public function createNutritionFoodItem(array $data): NutritionFoodItems
    {
        return NutritionFoodItems::create($data);
    }

    /**
     * Get nutrition food item
     */
    public function getNutritionFoodItem(int $id): ?NutritionFoodItems
    {
        return NutritionFoodItems::find($id);
    }

    /**
     * Get nutrition food items by nutrition version
     */
    public function getNutritionFoodItemsByVersion(int $versionId): \Illuminate\Database\Eloquent\Collection
    {
        return NutritionFoodItems::where('nutrition_version_id', $versionId)->get();
    }

    /**
     * Update nutrition food item
     */
    public function updateNutritionFoodItem(int $id, array $data): ?NutritionFoodItems
    {
        $nutritionFoodItem = NutritionFoodItems::find($id);

        if (!$nutritionFoodItem) {
            return null;
        }

        $nutritionFoodItem->update($data);

        return $nutritionFoodItem;
    }

    /**
     * Delete nutrition food item
     */
    public function deleteNutritionFoodItem(int $id): bool
    {
        $nutritionFoodItem = NutritionFoodItems::find($id);

        if (!$nutritionFoodItem) {
            return false;
        }

        $nutritionFoodItem->delete();

        return true;
    }

    /**
     * Get all nutrition food items
     */
    public function getAllNutritionFoodItems(): \Illuminate\Database\Eloquent\Collection
    {
        return NutritionFoodItems::all();
    }
}