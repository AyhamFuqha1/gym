<?php

namespace App\Services;

use App\Models\Nutrition;
use App\Models\NutritionFoodItems;
use App\Models\NutritionVersions;
use App\Models\ProgramExercises;
use App\Models\ProgramVersion;
use DB;
use Illuminate\Support\Facades\Log;
use Request;

class AdminPlanManagementService
{
    public function __construct(private NotificationService $notificationService)
    {
        //
    }

    public function getPendingTrainingPlans()
    {
        $plans = ProgramVersion::with([
            'exercises.exercise',
            'userProgram.user.Profile',
            'userProgram.user.goals',
        ])
            ->where('is_active', 'pending')
            ->get()
            ->map(function ($plan) {
                $user = $plan->userProgram?->user;

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'created_at' => $plan->created_at ?? null,

                    'user' => [
                        'id' => $user?->id,
                        'name' => $user?->name,
                        'email' => $user?->email,
                        'goal' => $user?->goals?->type ?? null,
                        'level' => $user?->Profile?->activity_level ?? null,
                    ],

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
        return DB::transaction(function () use ($request) {
            $request->validate([
                'plan_id' => 'required|exists:program_versions,id',
                'schedule' => 'required|array'
            ]);

            $oldPlan = ProgramVersion::findOrFail($request->plan_id);


            $user = \Illuminate\Support\Facades\Auth::user();

            if ($user->role_id == 2) {
                $sourceType = 'admin_edit';
            } elseif ($user->role_id == 3) {
                $sourceType = 'coach_edit';
            } else {
                $sourceType = $oldPlan->source_type;
            }

            $newPlan = ProgramVersion::create([
                'name' => $request->name ?? ($oldPlan->name . ' Edited'),
                'level' => $oldPlan->level,
                'user_program_id' => $oldPlan->user_program_id,
                'source_type' => $sourceType,
                'source_id' => $oldPlan->id,
                'is_active' => 'accepted',
            ]);

            foreach ($request->schedule as $day) {
                foreach ($day['exercises'] as $exercise) {
                    ProgramExercises::create([
                        'program_version_id' => $newPlan->id,
                        'exercise_id' => $exercise['id'],
                        'sets' => $exercise['sets'],
                        'reps' => $exercise['reps'],
                        'rest_seconds' => $exercise['rest_seconds'] ?? null,
                        'day_number' => $day['day_number'],
                        'difficulty' => $exercise['difficulty'] ?? 'medium',
                    ]);
                }
            }
            $oldPlan->update(['is_active' => 'cancel']);
            $this->notifyTrainingPlanApproved($newPlan, $this->trainingPlanRecipientUserId($oldPlan));
        });

    }

    public function getPendingNutritionPlans()
    {
        return NutritionVersions::with('foodItems.nutrition')
        ->where('is_active', 'pending')
        ->get()
        ->map(function ($plan) {
            return [
                'id' => $plan->id,
                'daily_calories' => $plan->daily_calories,
                'daily_protein' => $plan->daily_protein,
                'daily_carbs' => $plan->daily_carbs,
                'daily_fat' => $plan->daily_fat,
                'is_active' => $plan->is_active,
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
        return DB::transaction(function () use ($request) {
            $request->validate([
                'plan_id' => 'required|exists:nutrition_versions,id',
                'daily_meals' => 'required|array',
            ]);

            $oldPlan = NutritionVersions::findOrFail($request->plan_id);

            $daily_calories = 0;
            $daily_protein = 0;
            $daily_carbs = 0;
            $daily_fat = 0;
            $nutritionItems = [];

            foreach ($request->daily_meals as $meal) {
                foreach ($meal['items'] as $item) {
                    $daily_calories += $item['calories'] * $item['quantity'];
                    $daily_protein += $item['protein'] * $item['quantity'];
                    $daily_carbs += $item['carbs'] * $item['quantity'];
                    $daily_fat += $item['fat'] * $item['quantity'];
                }
            }

            $newPlan = NutritionVersions::create([
                'user_nutrition_plan_id' => $oldPlan->user_nutrition_plan_id,
                'daily_calories' => $daily_calories,
                'daily_protein' => $daily_protein,
                'daily_carbs' => $daily_carbs,
                'daily_fat' => $daily_fat,
                'reason' => $request->reason ?? null,
                'is_active' => 'active',
            ]);

            foreach ($request->daily_meals as $meal) {
                foreach ($meal['items'] as $item) {
                    $nutritionItems[] = [
                        'nutrition_version_id' => $newPlan->id,
                        'nutrition_id' => $item['food_id'],
                        'quantity' => $item['quantity'],
                        'meal_type' => $meal['meal'],
                    ];
                }
            }

            NutritionFoodItems::insert($nutritionItems);

            $oldPlan->update([
                'is_active' => 'cancel',
            ]);

            $this->notifyNutritionPlanApproved($newPlan, $this->nutritionPlanRecipientUserId($oldPlan));

            return [
                'status' => true,
                'message' => 'Nutrition plan updated successfully',
                'data' => $newPlan->load('foodItems.nutrition'),
            ];
        });
    }

    private function notifyTrainingPlanApproved(ProgramVersion $plan, ?int $recipientUserId): void
    {
        if (!$recipientUserId) {
            return;
        }

        try {
            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => auth()->id(),
                'type' => 'training_plan_approved',
                'title' => 'Training plan approved',
                'body' => 'Your training plan is ready.',
                'entity_type' => 'program_version',
                'entity_id' => $plan->id,
                'priority' => 'high',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'TrainingPlan',
                    'program_version_id' => $plan->id,
                    'training_plan_id' => $plan->id,
                    'user_program_id' => $plan->user_program_id,
                    'type' => 'training_plan_approved',
                    'entity_type' => 'program_version',
                    'entity_id' => $plan->id,
                    'status' => $plan->is_active,
                    'source_type' => $plan->source_type,
                    'source_id' => $plan->source_id,
                ], fn ($value) => $value !== null),
                'dedupe_key' => "training_plan_approved:{$plan->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create training plan approved notification.', [
                'program_version_id' => $plan->id,
                'recipient_user_id' => $recipientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyNutritionPlanApproved(NutritionVersions $plan, ?int $recipientUserId): void
    {
        if (!$recipientUserId) {
            return;
        }

        try {
            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => auth()->id(),
                'type' => 'nutrition_plan_approved',
                'title' => 'Nutrition plan approved',
                'body' => 'Your nutrition plan is ready.',
                'entity_type' => 'nutrition_version',
                'entity_id' => $plan->id,
                'priority' => 'high',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'NutritionPlan',
                    'nutrition_version_id' => $plan->id,
                    'nutrition_plan_id' => $plan->user_nutrition_plan_id,
                    'type' => 'nutrition_plan_approved',
                    'entity_type' => 'nutrition_version',
                    'entity_id' => $plan->id,
                    'status' => $plan->is_active,
                    'daily_calories' => $plan->daily_calories,
                ], fn ($value) => $value !== null),
                'dedupe_key' => "nutrition_plan_approved:{$plan->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create nutrition plan approved notification.', [
                'nutrition_version_id' => $plan->id,
                'recipient_user_id' => $recipientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function trainingPlanRecipientUserId(ProgramVersion $plan): ?int
    {
        if ($plan->user_id) {
            return (int) $plan->user_id;
        }

        $plan->loadMissing('userProgram');

        return $plan->userProgram?->user_id
            ? (int) $plan->userProgram->user_id
            : null;
    }

    private function nutritionPlanRecipientUserId(NutritionVersions $plan): ?int
    {
        $plan->loadMissing('userNutritionPlan');

        return $plan->userNutritionPlan?->user_id
            ? (int) $plan->userNutritionPlan->user_id
            : null;
    }

}
