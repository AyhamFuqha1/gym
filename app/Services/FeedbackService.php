<?php

namespace App\Services;

use App\Models\EquipmentReport;
use App\Models\User;
use App\Models\feedback;
use App\Models\Suggestion;
use App\Models\TrainerRating;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    protected $table = 'user_feedback';

    protected $fillable = [
        'user_id',
        'type',
        'content'
    ];
    public function dashboard()
    {
        $feedback = feedback::with(['equipment', 'trainerRating.trainer', 'suggestion', 'user'])
            ->orderByDesc('created_at')
            ->paginate(50);

        $feedback->setCollection($feedback->getCollection()->map(function ($item) {
            return $this->formatFeedback($item);
        }));

        return $feedback;
    }

    public function store(array $data, int $userId): array
    {
        $feedback = DB::transaction(function () use ($data, $userId) {
            $type = $this->normalizedType($data['type']);

            $feedback = feedback::create([
                'user_id' => $userId,
                'type' => $type,
                'content' => $data['content'],
            ]);

            if ($type === 'equipment') {
                EquipmentReport::create([
                    'feedback_id' => $feedback->id,
                    'equipment_name' => $data['equipment_name'],
                    'priority' => $data['priority'],
                    'status' => 'pending',
                ]);
            } elseif ($type === 'suggestion') {
                Suggestion::create([
                    'feedback_id' => $feedback->id,
                    'status' => 'pending',
                ]);
            } elseif ($type === 'rating') {
                TrainerRating::create([
                    'feedback_id' => $feedback->id,
                    'trainer_id' => $data['trainer_id'],
                    'rating' => $data['rating'],
                ]);
            }

            return $feedback->fresh(['equipment', 'suggestion', 'trainerRating.trainer', 'user']);
        });

        return $this->formatFeedback($feedback);
    }

    public function getMyFeedback(int $userId): array
    {
        return feedback::with(['equipment', 'suggestion', 'trainerRating.trainer', 'user'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (feedback $feedback) => $this->formatFeedback($feedback))
            ->all();
    }

    public function showForUser(int $id, int $userId): array
    {
        return $this->formatFeedback($this->findForUser($id, $userId));
    }

    public function show(int $id, User $actor): array
    {
        $feedback = $this->canManageFeedback($actor)
            ? $this->findFeedback($id)
            : $this->findForUser($id, $actor->id);

        return $this->formatFeedback($feedback);
    }

    public function updateForUser(array $data, int $id, int $userId): array
    {
        $feedback = DB::transaction(function () use ($data, $id, $userId) {
            $feedback = $this->findForUser($id, $userId);

            return $this->updateFeedbackModel($feedback, $data, false);
        });

        return $this->formatFeedback($feedback);
    }

    public function update(array $data, int $id, User $actor): array
    {
        $feedback = DB::transaction(function () use ($data, $id, $actor) {
            $canManageWorkflow = $this->canManageFeedback($actor);
            $feedback = $canManageWorkflow
                ? $this->findFeedback($id)
                : $this->findForUser($id, $actor->id);

            return $this->updateFeedbackModel($feedback, $data, $canManageWorkflow);
        });

        return $this->formatFeedback($feedback);
    }

    public function deleteForUser(int $id, int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $feedback = $this->findForUser($id, $userId);

            $feedback->equipment()->delete();
            $feedback->suggestion()->delete();
            $feedback->trainerRating()->delete();
            $feedback->delete();
        });
    }

    public function delete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor) {
            $feedback = $this->canManageFeedback($actor)
                ? $this->findFeedback($id)
                : $this->findForUser($id, $actor->id);

            $feedback->equipment()->delete();
            $feedback->suggestion()->delete();
            $feedback->trainerRating()->delete();
            $feedback->delete();
        });
    }

    private function findFeedback(int $id): feedback
    {
        return feedback::with(['equipment', 'suggestion', 'trainerRating.trainer', 'user'])
            ->findOrFail($id);
    }

    private function findForUser(int $id, int $userId): feedback
    {
        return feedback::with(['equipment', 'suggestion', 'trainerRating.trainer', 'user'])
            ->where('user_id', $userId)
            ->findOrFail($id);
    }

    private function updateFeedbackModel(feedback $feedback, array $data, bool $canManageWorkflow): feedback
    {
        $currentType = $this->normalizedType($feedback->type);

        if (array_key_exists('type', $data) && $this->normalizedType($data['type']) !== $currentType) {
            throw ValidationException::withMessages([
                'type' => 'Changing feedback type is not supported.',
            ]);
        }

        if (array_key_exists('content', $data)) {
            $feedback->update(['content' => $data['content']]);
        }

        $this->updateDetails($feedback, $data, $canManageWorkflow);

        return $feedback->fresh(['equipment', 'suggestion', 'trainerRating.trainer', 'user']);
    }

    private function updateDetails(feedback $feedback, array $data, bool $canManageWorkflow): void
    {
        $type = $this->normalizedType($feedback->type);

        if ($type === 'equipment') {
            $detail = $this->onlyPresent($data, ['equipment_name', 'priority', 'status']);

            if (!$canManageWorkflow) {
                unset($detail['status']);
            }

            if ($detail) {
                EquipmentReport::updateOrCreate(['feedback_id' => $feedback->id], $detail);
            }
        } elseif ($type === 'suggestion') {
            $detail = $this->onlyPresent($data, ['status']);

            if (!$canManageWorkflow) {
                unset($detail['status']);
            }

            if (isset($detail['status'])) {
                $detail['status'] = $this->normalizeSuggestionStatus($detail['status']);
            }

            if ($detail) {
                Suggestion::updateOrCreate(['feedback_id' => $feedback->id], $detail);
            }
        } elseif ($type === 'rating') {
            $detail = $this->onlyPresent($data, ['trainer_id', 'rating']);

            if ($detail) {
                TrainerRating::updateOrCreate(['feedback_id' => $feedback->id], $detail);
            }
        }
    }

    private function onlyPresent(array $data, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }

        return $values;
    }

    private function canManageFeedback(User $user): bool
    {
        return in_array((int) $user->role_id, [1, 2, 3], true);
    }

    private function normalizedType(string $type): string
    {
        return $type === 'trainer' ? 'rating' : $type;
    }

    private function normalizeSuggestionStatus(string $status): string
    {
        if ($status === 'new') {
            return 'pending';
        }

        if ($status === 'under_review') {
            return 'reviewed';
        }

        if ($status === 'resolved' || $status === 'accepted') {
            return 'implemented';
        }

        return $status;
    }

    private function formatFeedback(feedback $feedback): array
    {
        $data = [
            'id' => $feedback->id,
            'user_id' => $feedback->user_id,
            'user_name' => $feedback->user->name ?? 'Unknown',
            'type' => $this->normalizedType($feedback->type),
            'content' => $feedback->content,
            'created_at' => $feedback->created_at,
        ];

        if ($this->normalizedType($feedback->type) === 'equipment' && $feedback->equipment) {
            $data['details'] = [
                'name' => $feedback->equipment->equipment_name,
                'equipment_name' => $feedback->equipment->equipment_name,
                'priority' => $feedback->equipment->priority,
                'status' => $feedback->equipment->status,
            ];
        } elseif ($this->normalizedType($feedback->type) === 'suggestion' && $feedback->suggestion) {
            $data['details'] = [
                'status' => $this->normalizeSuggestionStatus($feedback->suggestion->status),
            ];
        } elseif ($this->normalizedType($feedback->type) === 'rating' && $feedback->trainerRating) {
            $data['details'] = [
                'trainer_id' => $feedback->trainerRating->trainer_id,
                'trainer_name' => $feedback->trainerRating->trainer->name ?? null,
                'trainer_email' => $feedback->trainerRating->trainer->email ?? null,
                'rating' => $feedback->trainerRating->rating,
            ];
        }

        return $data;
    }
}
