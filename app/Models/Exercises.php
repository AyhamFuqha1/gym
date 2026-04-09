<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercises extends Model
{
    public $timestamps = false;
    protected $table = 'exercises';

    protected $fillable = [
        'name',
        'general_exercise_id',
        'difficulty_level',
        'video_url',
        'instructions',
        'common_mistakes'
    ];

    public function category()
    {
        return $this->belongsTo(GeneralExercises::class, 'general_exercise_id');
    }

    public function programs()
    {
        return $this->hasMany(ProgramExercises::class, 'exercise_id', 'id');
    }

    public function oldAdjustments()
    {
        return $this->hasMany(InjuryProgramAdjustments::class, 'old_exercise_id');
    }

    public function newAdjustments()
    {
        return $this->hasMany(InjuryProgramAdjustments::class, 'new_exercise_id');
    }
}