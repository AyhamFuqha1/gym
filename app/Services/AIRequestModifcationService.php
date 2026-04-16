<?php

namespace App\Services;
use App\Models\UserNutritionPlans;
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
            'daily_meals' => 'required|array'
        ]);

        return DB::transaction(function () use ($request, $idModification) {
            $modificationRequest = ModificationRequest::findOrFail($idModification);

            if ($modificationRequest->type !== 'nutrition') {
                throw new \Exception('This request is not a nutrition request.');
            }

            if ($modificationRequest->status !== 'pending') {
                throw new \Exception('This request has already been processed.');
            }

            $userId = $modificationRequest->user_id;
            $dailyMeals = $request->daily_meals;
            $sourceType = $modificationRequest->source ?? 'generated';

            $userNutritionPlan = null;
            $oldVersion = null;

            if ($sourceType === 'modification') {
                $oldVersionId =
                    $request->input('plan_id') ??
                    data_get($modificationRequest->modified_plan, 'plan_id');

                if (!$oldVersionId) {
                    throw new \Exception('Old nutrition version id is required for modification approval.');
                }

                $oldVersion = NutritionVersions::findOrFail($oldVersionId);
                $userNutritionPlan = UserNutritionPlans::findOrFail($oldVersion->user_nutrition_plan_id);
            } else {
                $goalType =
                    data_get($modificationRequest->user_feedback, 'goal') ??
                    'muscle_gain';

                $userNutritionPlan = $this->userNutritionPlansService->createUserNutritionPlan([
                    'name' => 'My Nutrition Plan',
                    'user_id' => $userId,
                    'start_date' => now(),
                    'end_date' => now()->addWeeks(8),
                    'goal_type' => $goalType,
                    'active' => 'pending',
                ]);
            }

            $dailyCalories = 0;
            $dailyProtein = 0;
            $dailyCarbs = 0;
            $dailyFat = 0;

            foreach ($dailyMeals as $meal) {
                foreach (($meal['items'] ?? []) as $item) {
                    $quantity = (float) ($item['quantity'] ?? 1);

                    $dailyCalories += ((float) ($item['calories'] ?? 0)) * $quantity;
                    $dailyProtein += ((float) ($item['protein'] ?? 0)) * $quantity;
                    $dailyCarbs += ((float) ($item['carbs'] ?? 0)) * $quantity;
                    $dailyFat += ((float) ($item['fat'] ?? 0)) * $quantity;
                }
            }

            $newVersion = NutritionVersions::create([
                'user_nutrition_plan_id' => $userNutritionPlan->id,
                'reason' => $sourceType === 'modification' ? 'modification' : 'new',
                'is_active' => 'pending',
                'daily_calories' => $dailyCalories,
                'daily_protein' => $dailyProtein,
                'daily_carbs' => $dailyCarbs,
                'daily_fat' => $dailyFat,
            ]);

            $nutritionItems = [];

            foreach ($dailyMeals as $meal) {
                $mealType = ($meal['meal'] ?? '') === 'snacks' ? 'snack' : ($meal['meal'] ?? '');

                foreach (($meal['items'] ?? []) as $item) {
                    $nutritionItems[] = [
                        'nutrition_version_id' => $newVersion->id,
                        'nutrition_id' => $item['food_id'],
                        'quantity' => $item['quantity'] ?? 1,
                        'meal_type' => $mealType,
                    ];
                }
            }

            if (!empty($nutritionItems)) {
                NutritionFoodItems::insert($nutritionItems);
            }

            if ($oldVersion) {
                $oldVersion->update([
                    'is_active' => 'cancel',
                ]);
            }

            $newVersion->update([
                'is_active' => 'active',
            ]);

            $userNutritionPlan->update([
                'active' => 'active',
            ]);

            $modificationRequest->update([
                'status' => 'done',
            ]);

            return $newVersion;
        });
    }

    public function approveFinalTraining(Request $request, $idModification)
    {
        $request->validate([
            'plan_data' => 'required|array',
        ]);

        return DB::transaction(function () use ($request, $idModification) {
            $modificationRequest = ModificationRequest::findOrFail($idModification);

            if ($modificationRequest->type !== 'progress') {
                throw new \Exception('This request is not a training request.');
            }

            if ($modificationRequest->status !== 'pending') {
                throw new \Exception('This request has already been processed.');
            }

            $userId = $modificationRequest->user_id;
            $planData = $request->input('plan_data');

            $planDataContent = $planData['plan_data'] ?? $planData;
            $schedule = $planDataContent['schedule'] ?? [];
            $durationWeeks = $planDataContent['duration_weeks'] ?? 4;

            if (empty($schedule)) {
                throw new \Exception('Plan schedule is required.');
            }

            $userProgram = null;
            $oldPlan = null;
            $sourceType = 'generated';

            if ($modificationRequest->source === 'modification') {
                $oldPlan = ProgramVersion::findOrFail($modificationRequest->program_version_id);
                $userProgram = $oldPlan->userProgram;

                if (!$userProgram) {
                    throw new \Exception('Old plan is not linked to a user program.');
                }

                $sourceType = 'coach_edit';
            } else {
                $userProgram = $this->userProgramService->createUserProgram([
                    'user_id' => $userId,
                    'start_date' => now(),
                    'end_date' => now()->addWeeks($durationWeeks),
                    'status' => 'active',
                ]);

                $sourceType = 'generated';
            }

            $newPlan = $this->programVersionService->createProgramVersion([
                'user_id' => $userId,
                'name' => $planDataContent['version'] ?? 'My Training Plan',
                'level' => 'intermediate',
                'user_program_id' => $userProgram->id,
                'source_type' => $sourceType,
                'source_id' => $modificationRequest->id,
                'is_active' => 'accepted',
            ]);

            $exercises = [];

            foreach ($schedule as $day) {
                foreach (($day['exercises'] ?? []) as $exercise) {
                    $exercises[] = [
                        'program_version_id' => $newPlan->id,
                        'exercise_id' => $exercise['exercise_id'],
                        'sets' => $exercise['sets'],
                        'reps' => $exercise['reps'],
                        'rest_seconds' => $exercise['rest_seconds'] ?? null,
                        'day_number' => $day['day'],
                        'difficulty' => $exercise['difficulty'] ?? 'medium',
                    ];
                }
            }

            if (!empty($exercises)) {
                ProgramExercises::insert($exercises);
            }

            $userProgram->update([
                'program_version_id' => $newPlan->id,
                'status' => 'active',
                'start_date' => $userProgram->start_date ?? now(),
                'end_date' => now()->addWeeks($durationWeeks),
            ]);

            if ($oldPlan) {
                $oldPlan->update([
                    'is_active' => 'cancel',
                ]);
            }

            $modificationRequest->update([
                'status' => 'done',
            ]);

            return $newPlan;
        });
    }

}
