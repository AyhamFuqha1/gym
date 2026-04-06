<?php

namespace App\Services;

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

}
