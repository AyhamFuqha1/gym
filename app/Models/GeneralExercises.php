<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralExercises extends Model
{
    protected $table = 'general_exercises';

    protected $fillable = [
        'name',
        'muscle_group',
        'description',
    ];
    public $timestamps = false;

    public function exercises()
    {
        return $this->hasMany(Exercises::class, 'general_exercise_id');
    }
}
