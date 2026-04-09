<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramExercises extends Model
{
    protected $table = 'program_exercises';

    protected $fillable = [
        'program_version_id',
        'exercise_id',
        'sets',
        'reps',
        'rest_seconds',
        'difficulty',
        'day_number',
        'order_in_day'
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercises::class, 'exercise_id', 'id');
    }
}