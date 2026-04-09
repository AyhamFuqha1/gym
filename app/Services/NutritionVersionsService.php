<?php

namespace App\Services;

use App\Models\NutritionVersions;

class NutritionVersionsService
{
    /**
     * Create nutrition version
     */
    public function createNutritionVersion(array $data): NutritionVersions
    {
        return NutritionVersions::create($data);
    }

    /**
     * Get nutrition version
     */
    public function getNutritionVersion(int $id): ?NutritionVersions
    {
        return NutritionVersions::find($id);
    }

    /**
     * Get nutrition versions by user nutrition plan
     */
    public function getNutritionVersionsByPlan(int $planId): \Illuminate\Database\Eloquent\Collection
    {
        return NutritionVersions::where('user_nutrition_plan_id', $planId)->get();
    }

    /**
     * Update nutrition version
     */
    public function updateNutritionVersion(int $id, array $data): ?NutritionVersions
    {
        $nutritionVersion = NutritionVersions::find($id);

        if (!$nutritionVersion) {
            return null;
        }

        $nutritionVersion->update($data);

        return $nutritionVersion;
    }

    /**
     * Delete nutrition version
     */
    public function deleteNutritionVersion(int $id): bool
    {
        $nutritionVersion = NutritionVersions::find($id);

        if (!$nutritionVersion) {
            return false;
        }

        $nutritionVersion->delete();

        return true;
    }

    /**
     * Get all nutrition versions
     */
    public function getAllNutritionVersions(): \Illuminate\Database\Eloquent\Collection
    {
        return NutritionVersions::all();
    }
}