<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

class PlanService
{
    /**
     * Get all plans
     */
    public function getAllPlans(): Collection
    {
        return Plan::all();
    }

    /**
     * Create a new plan
     */
    public function createPlan(array $data): Plan
    {
        return Plan::create($data);
    }

    /**
     * Get a plan by ID
     */
    public function getPlan(int $id): ?Plan
    {
        return Plan::find($id);
    }

    /**
     * Update a plan
     */
    public function updatePlan(int $id, array $data): ?Plan
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return null;
        }

        $plan->update($data);

        return $plan;
    }

    /**
     * Delete a plan
     */
    public function deletePlan(int $id): bool
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return false;
        }

        return $plan->delete();
    }
}