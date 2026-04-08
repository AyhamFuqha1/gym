<?php

namespace App\Http\Controllers;

use App\Models\ModificationRequest;
use App\Models\NutritionFoodItems;
use App\Models\NutritionVersions;
use App\Models\ProgramExercises;
use App\Models\ProgramVersion;
use App\Models\User;
use App\Models\UserNutritionPlans;
use App\Models\UserProgram;
use App\Services\ProgramVersionService;
use App\Services\UserNutritionPlansService;
use App\Services\UserProgramService;
use App\Services\NutritionVersionsService;
use DB;
use Http;
use Illuminate\Http\Request;
use Log;

class AIController extends Controller
{
    protected $pythonApiUrl;
    protected $userProgramService;
    protected $programVersionService;
    protected $userNutritionPlansService;
    protected $NutritionVersionsService;

    public function __construct(UserProgramService $userProgramService, ProgramVersionService $programVersionService, UserNutritionPlansService $userNutritionPlansService, NutritionVersionsService $NutririonVersionService)
    {
        $this->pythonApiUrl = config('services.python_ai.url', 'http://localhost:8001');
        $this->userProgramService = $userProgramService;
        $this->programVersionService = $programVersionService;
        $this->userNutritionPlansService = $userNutritionPlansService;
        $this->NutritionVersionsService = $NutririonVersionService;
    }

    public function syncAll(Request $request)
    {
        try {
            $fullSync = $request->input('full_sync', false);

            $response = Http::post("{$this->pythonApiUrl}/sync-all", [
                'full_sync' => $fullSync
            ]);
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Sync All Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchExercises(Request $request)
    {
        try {
            $query = $request->input('query');
            $nResults = $request->input('n_results', 10);

            $response = Http::post("{$this->pythonApiUrl}/search-exercises", [
                'query' => $query,
                'n_results' => $nResults
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Search Exercises Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchFoods(Request $request)
    {
        try {
            $query = $request->input('query');
            $nResults = $request->input('n_results', 10);
            $url = "{$this->pythonApiUrl}/search-foods?query=" . urlencode($query) . "&n_results=" . $nResults;

            $response = Http::post($url);  // استخدم GET مش POST


            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Search Foods Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generateTrainingPlan(Request $request)
    {
        try {
            $id = $request->validate([
                'id' => 'required|integer|exists:users,id',
            ])['id'];
            //if (UserProgram::where('status', 'pending')->where('user_id', $id)->exists()) {
            //          return response()->json(['error' => 'You already have a pending training plan. Please activate or delete it before creating a new one.'], 400);
            //   }
            $userSummary = $this->prepareUserSummary($id);
            $preferences = $request->input('preferences');
            if (is_null($preferences) || $preferences === []) {
                $preferences = new \stdClass();
            }


            $response = Http::post("{$this->pythonApiUrl}/generate-training-plan", [
                'user_summary' => $userSummary,
                'preferences' => $preferences
            ]);

            $planData = $response->json();


            $plan = $this->saveTrainingPlan($id, $planData);

            return response()->json($planData, $response->status());
        } catch (\Exception $e) {
            Log::error('Generate Training Plan Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generateNutritionPlan(Request $request)
    {
        try {
            $id = $request->validate([
                'id' => 'required|integer|exists:users,id',
            ])['id'];
            //if (UserNutritionPlans::where('active', 'pending')->where('user_id', $id)->exists()) {
            //     return response()->json(['error' => 'You already have a pending nutrition plan. Please activate or delete it before creating a new one.'], 400);
            //}
            $userSummary = $this->prepareUserSummary($id);
            $preferences = $request->input('preferences');
            if (is_null($preferences) || $preferences === []) {
                $preferences = new \stdClass();
            }
            $response = Http::post("{$this->pythonApiUrl}/generate-nutrition-plan", [
                'user_summary' => $userSummary,
                'preferences' => $preferences
            ]);

            $planData = $response->json();


            $this->saveNutritionPlan($id, $planData, $userSummary['goal']);

            return response()->json($planData, $response->status());
        } catch (\Exception $e) {
            Log::error('Generate Nutrition Plan Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function modifyTrainingPlan(Request $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $id = $request->validate([
                    'id' => 'required|integer|exists:users,id',
                ])['id'];
                $currentPlanId = $request->validate([
                    'current_plan_id' => 'required|integer|exists:program_versions,id',
                ])['current_plan_id'];
                $userFeedback = $request->input('user_feedback', []);

                $oldPlan = ProgramVersion::with(['exercises.exercise'])
                    ->where('id', $currentPlanId)
                    ->first();

                $currentPlanData = [
                    'plan_id' => $oldPlan->id,
                    'version' => $oldPlan->id,
                    'plan_data' => [
                        'schedule' => $oldPlan->exercises
                            ->groupBy('day_number')
                            ->map(function ($dayExercises, $day) {
                                return [
                                    'day' => (int) $day,

                                    'exercises' => $dayExercises->map(function ($ex) {
                                        return [
                                            'exercise_id' => $ex->exercise_id,
                                            'name' => $ex->exercise->name ?? null,
                                            'sets' => (int) $ex->sets,
                                            'reps' => (string) $ex->reps,
                                            'rest_seconds' => $ex->rest_seconds,
                                            'muscle_group' => $ex->exercise->muscle_group ?? null,
                                            'difficulty' => $ex->difficulty,
                                        ];
                                    })->values()
                                ];
                            })
                            ->sortBy('day')
                            ->values()
                    ]
                ];

                $userSummary = $this->prepareUserSummary($id);

                $response = Http::post("{$this->pythonApiUrl}/modify-training-plan", [
                    'current_plan_id' => $currentPlanId,
                    'current_plan' => $currentPlanData,
                    'user_summary' => $userSummary,
                    'adjustments' => [],
                    'user_feedback' => $userFeedback
                ]);

                $suggestions = $response->json();

                $modRequest = ModificationRequest::create([
                    'user_id' => $id,
                    'program_version_id' => $oldPlan->id,
                    'changes_summary' => $suggestions['changes_summary'] ?? [],
                    'modified_plan' => $suggestions['modified_plan'] ?? [],
                    'recommendations' => $suggestions['recommendations'] ?? [],
                    'user_feedback' => $userFeedback,
                    'source' => 'ai',
                    'source_id' => null,
                ]);


                return response()->json([
                    'data' => $suggestions,
                    'modification_request_id' => $modRequest->id
                ], $response->status());

            } catch (\Exception $e) {
                Log::error('Modify Training Plan Error: ' . $e->getMessage());
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });

    }

    public function modifyNutritionPlan(Request $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $id = $request->validate([
                    'id' => 'required|integer|exists:users,id',
                ])['id'];
                $currentPlanId = $request->validate([
                    'current_plan_id' => 'required|integer|exists:program_versions,id',
                ])['current_plan_id'];
                $userFeedback = $request->input('user_feedback', []);
                $oldPlan = NutritionVersions::with('foodItems')
                    ->where('id', $currentPlanId)
                    ->first();


                $dailyMeals = $oldPlan->foodItems
                    ->groupBy('meal_type')
                    ->map(function ($items, $mealType) {
                        return [
                            'meal_type' => $mealType,
                            'foods' => $items->map(function ($item) {
                                return [
                                    'nutrition_id' => $item->nutrition_id,
                                    'quantity' => $item->quantity,
                                ];
                            })->values()
                        ];
                    })
                    ->values();

                $totalDaily = [
                    'calories' => (int) $oldPlan->daily_calories,
                    'protein' => (float) $oldPlan->daily_protein,
                    'carbs' => (float) $oldPlan->daily_carbs,
                    'fat' => (float) $oldPlan->daily_fat,
                ];

                $currentPlanData = [
                    'plan_id' => $oldPlan->id,
                    'version' => $oldPlan->id, //
                    'daily_meals' => $dailyMeals,
                    'total_daily' => $totalDaily,
                ];
                $userSummary = $this->prepareUserSummary($id);
                $response = Http::post("{$this->pythonApiUrl}/modify-nutrition-plan", [
                    'current_plan_id' => $currentPlanId,
                    'current_plan' => $currentPlanData,
                    'user_summary' => $userSummary,
                    'adjustments' => [],
                    'user_feedback' => $userFeedback
                ]);
                $suggestions = $response->json();
                $modRequest = ModificationRequest::create([
                    'user_id' => $id,
                    'program_version_id' => $oldPlan->id,
                    'changes_summary' => $suggestions['changes_summary'] ?? [],
                    'modified_plan' => $suggestions['modified_plan'] ?? [],
                    'recommendations' => $suggestions['recommendations'] ?? [],
                    'user_feedback' => $userFeedback,
                    'source' => 'ai',
                    'source_id' => null,
                ]);
                $suggestions = $response->json();
                return response()->json([
                    'data' => $suggestions,
                    'modification_request_id' => $modRequest->id
                ], $response->status());

            } catch (\Exception $e) {
                Log::error('Modify Nutrition Plan Error: ' . $e->getMessage());
                return response()->json(['error' => $e->getMessage()], 500);
            }
        });

    }

    public function analyzeProgress(Request $request)
    {
        try {
            $id = $request->validate([
                'id' => 'required|integer|exists:users,id',
            ])['id'];
            $currentPlanId = $request->validate([
                'current_plan_id' => 'required|integer|exists:program_versions,id',
            ])['current_plan_id'];
            $userSummary = $this->prepareUserSummary($id);
            $progressData = $request->input('progress_data');
            if (is_null($progressData) || $progressData === []) {
                $progressData = new \stdClass();
            }

            $response = Http::post("{$this->pythonApiUrl}/analyze-progress", [
                'user_summary' => $userSummary,
                'progress_data' => $progressData,
                'current_plan_id' => $currentPlanId
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Analyze Progress Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function prepareUserSummary($id)
    {
        $user = User::with(['Profile', 'goals', 'injuriesActive'])->find($id);
        return [
            'user_id' => $user->id,
            'level' => $user->Profile->activity_level ?? 'beginner',
            'goal' => $user->goals->type ?? 'muscle_gain',
            'training_age_years' => $user->age ?? 25,
            'injuries' => $user->injuriesActive->pluck('injury_type')->values()->toArray(),
            'weak_points' => [],
            'status' => 'progressing',
            'progress_rate' => 0.0,
            'consistency_score' => 0.0,
            'weight' => $user->Profile->weight ?? null,
            'height' => $user->Profile->height ?? null,
            'age' => $user->age ?? null,
        ];
    }

    private function saveTrainingPlan($userId, $planData)
    {
        return DB::transaction(function () use ($userId, $planData) {
            $planDataContent = $planData['plan_data'] ?? $planData;

            $UserProgram = $this->userProgramService->createUserProgram([
                'user_id' => $userId,
                'start_date' => now(),
                'end_date' => now()->addWeeks($planDataContent['duration_weeks'] ?? 4),
                'status' => 'pending',
            ]);

            $programVersion = $this->programVersionService->createProgramVersion([
                'name' => $planDataContent['version'] ?? 'My Training Plan',
                'level' => 'intermediate',
                'user_programme_id' => $UserProgram->id,
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
    private function saveNutritionPlan($userId, $planData, $goalType)
    {
        return DB::transaction(function () use ($userId, $planData, $goalType) {
            $UserNutrition = $this->userNutritionPlansService->createUserNutritionPlan([
                'name' => $planData['version'] ?? 'My Nutrition Plan',
                'user_id' => $userId,
                'start_date' => now(),
                'end_date' => now()->addWeeks(8),
                'goal_type' => $goalType,
                'active' => 'pending',
            ]);


            $programNutrition = $this->NutritionVersionsService->createNutritionVersion([
                'user_nutrition_plan_id' => $UserNutrition->id,
                'daily_calories' => $planData['total_daily']['calories'] ?? 2000,
                'daily_protein' => $planData['total_daily']['protein'] ?? 2000,
                'daily_carbs' => $planData['total_daily']['carbs'] ?? 250,
                'daily_fat' => $planData['total_daily']['fat'] ?? 70,
                'is_active' => 'pending',
                'reason' => 'new',
            ]);

            $Nutrition = [];
            foreach ($planData['daily_meals'] as $meal) {
                foreach ($meal['items'] as $item) {
                    $Nutrition[] = [
                        'nutrition_version_id' => $programNutrition->id,
                        'nutrition_id' => $item['food_id'],
                        'quantity' => $item['quantity'],
                        'meal_type' => $meal['meal'],
                    ];
                }
            }
            if (!empty($Nutrition)) {
                NutritionFoodItems::insert($Nutrition);
            }

            return $UserNutrition;
        });
    }

}

