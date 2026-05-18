<?php

namespace App\Services;

use App\Models\ModificationRequest;
use App\Models\ProgramExercises;
use App\Models\ProgramVersion;
use App\Models\User;
use App\Models\UserProgram;
use Illuminate\Support\Facades\Http;

class TrainingPlanModificationService
{
    protected string $pythonApiUrl;

    public function __construct()
    {
        $this->pythonApiUrl = rtrim(config('services.python_ai.url', 'http://ai:8001'), '/');
    }

    public function findCurrentActiveProgramVersionForUser(int $userId): ?ProgramVersion
    {
        $activeUserProgram = UserProgram::where('user_id', $userId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if ($activeUserProgram) {
            if ($activeUserProgram->program_version_id) {
                $programVersion = ProgramVersion::where('id', $activeUserProgram->program_version_id)
                    ->where('is_active', 'accepted')
                    ->first();

                if ($programVersion) {
                    return $programVersion;
                }
            }

            $programVersion = ProgramVersion::where('user_program_id', $activeUserProgram->id)
                ->where('is_active', 'accepted')
                ->orderByDesc('id')
                ->first();

            if ($programVersion) {
                return $programVersion;
            }
        }

        return ProgramVersion::where('user_id', $userId)
            ->where('is_active', 'accepted')
            ->orderByDesc('id')
            ->first();
    }

    public function createTrainingModificationRequest(
        int $userId,
        $currentPlanId,
        array $userFeedback,
        string $source = 'modification',
        ?int $sourceId = null
    ): array {
        $userSummary = $this->prepareUserSummary($userId);
        $currentPlanId = (string) $currentPlanId;
        $oldPlan = ProgramVersion::findOrFail($currentPlanId);

        $oldExercises = ProgramExercises::where('program_version_id', $oldPlan->id)
            ->with('exercise')
            ->get()
            ->map(function ($item) {
                return [
                    'exercise_id' => $item->exercise_id,
                    'name' => $item->exercise->name ?? null,
                    'sets' => $item->sets,
                    'reps' => $item->reps,
                    'rest_seconds' => $item->rest_seconds,
                    'muscle_group' => $item->exercise->muscle_group ?? null,
                    'difficulty' => $item->difficulty ?? 'beginner',
                    'day' => $item->day_number,
                ];
            })
            ->groupBy('day')
            ->map(function ($group, $day) {
                return [
                    'day' => (int) $day,
                    'exercises' => array_values($group->toArray()),
                ];
            })
            ->values()
            ->toArray();

        $payload = [
            'id' => $userId,
            'current_plan_id' => $currentPlanId,
            'user_summary' => $userSummary,
            'user_feedback' => $userFeedback,
            'current_plan' => [
                'plan_id' => $oldPlan->id,
                'version' => 1,
                'plan_data' => [
                    'schedule' => $oldExercises,
                ],
            ],
        ];

        $response = Http::timeout(120)->post("{$this->pythonApiUrl}/modify-training-plan", $payload);

        if (!$response->successful()) {
            return [
                'success' => false,
                'response' => $response->json(),
                'status' => $response->status(),
                'warning' => 'AI training modification request could not be created.',
            ];
        }

        $data = $response->json();

        if (!is_array($data)) {
            return [
                'success' => false,
                'response' => [
                    'success' => false,
                    'message' => 'Invalid response returned from AI service.',
                ],
                'status' => 500,
                'warning' => 'Invalid response returned from AI service.',
            ];
        }

        $result = isset($data['data']) && is_array($data['data'])
            ? $data['data']
            : $data;

        $modifiedPlan = $result['modified_plan'] ?? null;
        $recommendations = $result['recommendations'] ?? [];
        $changesSummary = $result['changes_summary'] ?? [];

        if (!$modifiedPlan || !is_array($modifiedPlan)) {
            return [
                'success' => false,
                'response' => [
                    'success' => false,
                    'message' => 'AI did not return a valid modified plan.',
                ],
                'status' => 500,
                'warning' => 'AI did not return a valid modified plan.',
            ];
        }

        $modRequest = ModificationRequest::create([
            'user_id' => $userId,
            'program_version_id' => $oldPlan->id,
            'type' => 'progress',
            'status' => 'pending',
            'changes_summary' => $changesSummary,
            'modified_plan' => $modifiedPlan,
            'recommendations' => $recommendations,
            'user_feedback' => $userFeedback,
            'source' => $source,
            'source_id' => $sourceId,
        ]);

        return [
            'success' => true,
            'data' => $result,
            'modification_request' => $modRequest,
        ];
    }

    private function prepareUserSummary(int $id): array
    {
        $user = User::with([
            'Profile',
            'goals',
            'injuriesActive',
            'likedFoods',
            'dislikedFoods'
        ])->findOrFail($id);

        $goalRecord = $user->goals instanceof \Illuminate\Support\Collection
            ? $user->goals->first()
            : $user->goals;

        return [
            'user_id' => $user->id,
            'level' => $user->Profile->activity_level ?? 'beginner',
            'goal' => optional($goalRecord)->goal_type ?? 'muscle_gain',
            'target_weight' => optional($goalRecord)->target_weight ?? null,
            'training_age_years' => $user->age ?? 25,
            'injuries' => $user->injuriesActive->pluck('injury_type')->values()->toArray(),
            'weak_points' => [],
            'status' => 'progressing',
            'progress_rate' => 0.0,
            'consistency_score' => 0.0,
            'weight' => $user->Profile->weight ?? null,
            'height' => $user->Profile->height ?? null,
            'age' => $user->age ?? null,
            'food_allergies' => $user->Profile->food_allergies ?? null,
            'medical_conditions' => $user->Profile->medical_conditions ?? null,
            'preferences' => $user->Profile->preferences ?? null,
            'liked_foods' => $user->likedFoods->pluck('name')->values()->toArray(),
            'disliked_foods' => $user->dislikedFoods->pluck('name')->values()->toArray(),
        ];
    }
}
