<?php

namespace App\Services;

use App\Models\Nutrition;
use App\Models\NutritionFoodItems;
use App\Models\NutritionVersions;
use App\Models\ProgramExercises;
use App\Models\ProgramVersion;
use Request;

class AdminPlanManagementService
{
    public function getPendingTrainingPlans()
    {
        $plans = ProgramVersion::with(['exercises.exercise'])
            ->where('is_active', 'pending')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'exercises' => $plan->exercises->map(function ($exercise) {
                        return [
                            'id' => $exercise->exercise_id,
                            'name' => $exercise->exercise->name,
                            'sets' => $exercise->sets,
                            'reps' => $exercise->reps,
                            'rest_seconds' => $exercise->rest_seconds,
                            'difficulty' => $exercise->difficulty,
                            'day_number' => $exercise->day_number,
                        ];
                    }),
                ];
            });

        return $plans;
    }


    public function saveEditedTrainingPlan($request)
    {
        $request->validate([
            'plan_id' => 'required|exists:program_versions,id',
            'schedule' => 'required|array'
        ]);

        $oldPlan = ProgramVersion::findOrFail($request->plan_id);


        $newPlan = ProgramVersion::create([
            'name' => 'version_' . time(),
            'level' => $oldPlan->level,
            'user_programme_id' => $oldPlan->user_programme_id,
            'source_type' => 'admin_edit',
            'source_id' => $oldPlan->id,
            'is_active' => 'accepted',
        ]);


        foreach ($request->schedule as $day) {
            foreach ($day['exercises'] as $exercise) {
                ProgramExercises::create([
                    'program_version_id' => $newPlan->id,
                    'exercise_id' => $exercise['exercise_id'],
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'rest_seconds' => $exercise['rest_seconds'] ?? null,
                    'day_number' => $day['day'],
                    'difficulty' => $exercise['difficulty'] ?? 'medium',
                ]);
            }
        }
    }

    public function getPendingNutritionPlans()
    {
        return NutritionVersions::with('foodItems.nutrition')->where('is_active', 'pending')->get()->map(function ($plan) {
            return [
                'id' => $plan->id,
                'daily_calories' => $plan->daily_calories,
                'daily_protein' => $plan->daily_protein,
                'daily_carbs' => $plan->daily_carbs,
                'daily_fat' => $plan->daily_fat,
                'reason' => $plan->reason,
                'food_items' => $plan->foodItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->nutrition->name ? $item->nutrition->name : null,
                        'quantity' => $item->quantity,
                        'meal_type' => $item->meal_type,
                    ];
                }),
            ];
        });
    }

    public function saveEditedNutritionPlan($request)
    {

        $request->validate([
            'plan_id' => 'required|exists:nutrition_versions,id',
            'daily_meals' => 'required|array'
        ]);
        $oldPlan = NutritionVersions::findOrFail($request->plan_id);

        $newPlan = NutritionVersions::create([
            'user_nutrition_plan_id' => $oldPlan->user_nutrition_plan_id,
            'reason' => $request->reason ?? null,
            'is_active' => 'active',
        ]);
        $daily_calories = 0;
        $daily_protein = 0;
        $daily_carbs = 0;
        $daily_fat = 0;
        $Nutrition = [];

        foreach ($request->daily_meals as $meal) {
             collect($meal['items'])->each(function ($item) use (&$Nutrition, &$newPlan, &$daily_calories, &$daily_protein, &$daily_carbs, &$daily_fat, $meal) {
                $Nutrition[] = [
                    'nutrition_version_id' => $newPlan->id,
                    'nutrition_id' => $item['food_id'],
                    'quantity' => $item['quantity'],
                    'meal_type' => $meal['meal'],
                ];

                $daily_calories += $item['calories'] * $item['quantity'];
                $daily_protein += $item['protein'] * $item['quantity'];
                $daily_carbs += $item['carbs'] * $item['quantity'];
                $daily_fat += $item['fat'] * $item['quantity'];
            });
        }
        NutritionFoodItems::insert($Nutrition);
        $newPlan->update([
            'daily_calories' => $daily_calories,
            'daily_protein' => $daily_protein,
            'daily_carbs' => $daily_carbs,
            'daily_fat' => $daily_fat,
        ]);
    }


}
