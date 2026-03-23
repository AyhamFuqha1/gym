<?php

namespace App\Services;

use App\Models\feedback;

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
        $feedback = feedback::with('equipment', 'trainerRating', 'suggestion', 'user')->paginate(15);
        $feedbacks = $feedback->getCollection()->transform(function ($item) {
            $data = [
                'id' => $item->id,
                'user_name' => $item->user->name ?? 'Unknown',
                'type' => $item->type,
                'content' => $item->content,
                'created_at' => $item->created_at,
            ];
            if ($item->type == 'equipment' && $item->equipment) {
                $data['details'] = [
                    'name' => $item->equipment->equipment_name,
                    'priority' => $item->equipment->priority,
                    'status' => $item->equipment->status,
                ];
            } elseif ($item->type == 'trainer' && $item->trainerRating) {
                $data['details'] = [
                    'rating' => $item->trainerRating->rating,
                ];
            } elseif ($item->type == 'suggestion' && $item->suggestion) {
                $data['details'] = [
                    'note' => $item->suggestion->note,
                ];
            }
            return $data;
        });
        return $feedback;
    }
}
