<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercises extends Model
{
     protected $table = 'exercises';

    protected $fillable = [
        'name',
        'general_exercise_id',
        'difficulty_level',
        'video_url',
        'duration_weeks',
        'goal_type'
    ];

   
    public function category()
    {
        return $this->belongsTo(GeneralExercises::class, 'general_exercise_id');
    }
}
