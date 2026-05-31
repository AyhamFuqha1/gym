<?php

namespace App\Services;

use App\Models\InjuryProgramAdjustments;
use App\Models\ModificationRequest;
use App\Models\UserInjuries;
use Illuminate\Support\Facades\Log;

class UserInjuriesService
{
    private TrainingPlanModificationService $trainingPlanModificationService;

    public function __construct(TrainingPlanModificationService $trainingPlanModificationService)
    {
        $this->trainingPlanModificationService = $trainingPlanModificationService;
    }

    public function index()
    {
        return UserInjuries::all();
    }

    public function store($data)
    {
        $injury = UserInjuries::create($data);

        $aiModification = [
            'created' => false,
            'modification_request_id' => null,
            'warning' => null,
        ];

        try {
            $currentPlan = $this->trainingPlanModificationService
                ->findCurrentActiveProgramVersionForUser((int) $injury->user_id);

            if (!$currentPlan) {
                $aiModification['warning'] = 'No active training plan found for this user; injury was saved without an AI modification request.';

                return [
                    'injury' => $injury,
                    'ai_modification' => $aiModification,
                ];
            }

            $result = $this->trainingPlanModificationService->createTrainingModificationRequest(
                (int) $injury->user_id,
                $currentPlan->id,
                [
                    'request_type' => 'injury',
                    'injury_id' => $injury->id,
                    'injury_type' => $injury->injury_type,
                    'severity' => $injury->severity,
                    'notes' => $injury->notes,
                    'status' => $injury->status,
                ],
                'injury',
                $injury->id
            );

            if ($result['success'] ?? false) {
                $aiModification['created'] = true;
                $aiModification['modification_request_id'] = $result['modification_request']->id;
            } else {
                $aiModification['warning'] = $result['warning'] ?? 'AI training modification request could not be created.';

                Log::warning('Injury AI training modification request was not created.', [
                    'injury_id' => $injury->id,
                    'user_id' => $injury->user_id,
                    'status' => $result['status'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            $aiModification['warning'] = 'AI training modification request could not be created.';

            Log::error('Injury AI training modification request failed.', [
                'injury_id' => $injury->id,
                'user_id' => $injury->user_id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'injury' => $injury,
            'ai_modification' => $aiModification,
        ];
    }

    public function show($id)
    {
        return UserInjuries::find($id);
    }

    public function update($id, $data)
    {
        $Injuries = UserInjuries::findOrFail($id);
        $Injuries->update($data);
        return $Injuries->fresh();
    }

    public function destroy($id)
    {
        $Injuries = UserInjuries::findOrFail($id);
        return $Injuries->delete();
    }

    public function dashboard()
    {
        $injuries = UserInjuries::with(['user', 'adjustments.oldExercise', 'adjustments.newExercise'])->paginate(15);
        $injuryIds = $injuries->getCollection()->pluck('id');

        $modificationRequests = $injuryIds->isEmpty()
            ? collect()
            : ModificationRequest::where('source', 'injury')
                ->whereIn('source_id', $injuryIds)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->unique('source_id')
                ->keyBy('source_id');

        $injuries->getCollection()->transform(function ($injury) use ($modificationRequests) {
            $modificationRequest = $modificationRequests->get($injury->id);

            return [
                'id' => $injury->id,
                'user_name' => $injury->user->name,
                'injury_type' => $injury->injury_type,
                'severity' => $injury->severity,
                'status' => $injury->status,
                'exercise_restrictions' => $injury->adjustments->map(function ($adj) {
                    return $adj->oldExercise->name ?? 'Unknown Exercise';
                }),
                'ai_alternatives' => $injury->adjustments->map(function ($adj) {
                    return $adj->newExercise->name ?? 'No Alternative';
                }),
                'modification_request' => $modificationRequest ? [
                    'id' => $modificationRequest->id,
                    'type' => $modificationRequest->type,
                    'status' => $modificationRequest->status,
                    'source' => $modificationRequest->source,
                    'source_id' => $modificationRequest->source_id,
                    'recommendations' => $modificationRequest->recommendations,
                    'changes_summary' => $modificationRequest->changes_summary,
                    'modified_plan' => $modificationRequest->modified_plan,
                    'user_feedback' => $modificationRequest->user_feedback,
                    'created_at' => $modificationRequest->created_at,
                    'updated_at' => $modificationRequest->updated_at,
                ] : null,
            ];
        });

        return $injuries;
    }
}
