<?php

namespace App\Services;

use App\Models\ModificationRequest;
use App\Models\NutritionFoodItems;
use App\Models\NutritionVersions;
use App\Models\ProgramExercises;
use App\Models\ProgramVersion;
use DB;
use Illuminate\Http\Request;

class AIRequestModifcationService
{

    protected $userProgramService;
    protected $programVersionService;
    protected $userNutritionPlansService;
    protected $NutritionVersionsService;

    public function __construct(UserProgramService $userProgramService, ProgramVersionService $programVersionService, UserNutritionPlansService $userNutritionPlansService, NutritionVersionsService $NutririonVersionService)
    {
        $this->userProgramService = $userProgramService;
        $this->programVersionService = $programVersionService;
        $this->userNutritionPlansService = $userNutritionPlansService;
        $this->NutritionVersionsService = $NutririonVersionService;
    }
    public function getTrainingModificationRequests()
    {
        return ModificationRequest::where('type', 'progress')
            ->where('status', 'pending')
            ->get();
    }

    public function getNutritionModificationRequests()
    {
        return ModificationRequest::where('type', 'nutrition')
            ->where('status', 'pending')
            ->get();
    }

    public function approveTraining(Request $request, $idModification)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_data' => 'required|array',
        ]);

        return DB::transaction(function () use ($request, $idModification) {
            $modificationRequest = ModificationRequest::findOrFail($idModification);
            $modificationRequest->update(['status' => 'done']);

            $userId = $request->user_id;
            $planDataContent = $request->plan_data['plan_data'] ?? $request->plan_data;

            $UserProgram = $this->userProgramService->createUserProgram([
                'user_id' => $userId,
                'start_date' => now(),
                'end_date' => now()->addWeeks($planDataContent['duration_weeks'] ?? 4),
                'status' => 'pending',
            ]);

            $programVersion = $this->programVersionService->createProgramVersion([
                'name' => $planDataContent['version'] ?? 'My Training Plan',
                'level' => 'intermediate',
                'user_program_id' => $UserProgram->id,
                'is_active' => 'pending',
                'source_type' => 'new',
                'source_id' => null,
            ]);

            $exercises = [];
            $schedule = $planDataContent['schedule'] ?? [];

            foreach ($schedule as $day) {
                foreach ($day['exercises'] as $exercise) {
                    $exercises[] = [
                        'program_version_id' => $programVersion->id,
                        'exercise_id' => $exercise['exercise_id'],
                        'sets' => $exercise['sets'],
                        'reps' => $exercise['reps'],
                        'rest_seconds' => $exercise['rest_seconds'],
                        'day_number' => $day['day'],
                        'difficulty' => $exercise['difficulty'],
                    ];
                }
            }

            if (!empty($exercises)) {
                ProgramExercises::insert($exercises);
            }

            return $UserProgram;
        });
    }

    public function approveNutrition(Request $request, $idModification)
    {
        $request->validate([
            'plan_id' => 'required|exists:nutrition_versions,id',
            'daily_meals' => 'required|array'
        ]);
        return DB::transaction(function () use ($request, $idModification) {
            $modificationRequest = ModificationRequest::findOrFail($idModification);
            $modificationRequest->update(['status' => 'done']);
            $oldPlan = NutritionVersions::findOrFail($request->plan_id);

            $newPlan = NutritionVersions::create([
                'user_nutrition_plan_id' => $oldPlan->user_nutrition_plan_id,
                'reason' => $request->reason ?? null,
                'is_active' => 'active',
                'daily_calories' => 0,
                'daily_protein' => 0,
                'daily_carbs' => 0,
                'daily_fat' => 0,
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
        });
    }
}
