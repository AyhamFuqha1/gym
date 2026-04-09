<?php

namespace App\Services;

use App\Models\UserNutritionPlans;

class UserNutritionPlansService
{
    /**
     * Create user nutrition plan
     */
    public function createUserNutritionPlan(array $data): UserNutritionPlans
    {
        return UserNutritionPlans::create($data);
    }

    /**
     * Get user nutrition plan
     */
    public function getUserNutritionPlan(int $id): ?UserNutritionPlans
    {
        return UserNutritionPlans::find($id);
    }

    /**
     * Get user nutrition plans by user
     */
    public function getUserNutritionPlansByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return UserNutritionPlans::where('user_id', $userId)->get();
    }

    /**
     * Update user nutrition plan
     */
    public function updateUserNutritionPlan(int $id, array $data): ?UserNutritionPlans
    {
        $userNutritionPlan = UserNutritionPlans::find($id);

        if (!$userNutritionPlan) {
            return null;
        }

        $userNutritionPlan->update($data);

        return $userNutritionPlan;
    }

    /**
     * Delete user nutrition plan
     */
    public function deleteUserNutritionPlan(int $id): bool
    {
        $userNutritionPlan = UserNutritionPlans::find($id);

        if (!$userNutritionPlan) {
            return false;
        }

        $userNutritionPlan->delete();

        return true;
    }
}