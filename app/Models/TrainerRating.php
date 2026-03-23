<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerRating extends Model
{
     protected $table = 'trainer_ratings';

    protected $fillable = [
        'feedback_id',
        'trainer_id',
        'rating'
    ];
    public function feedback()
    {
        return $this->belongsTo(feedback::class, 'feedback_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
