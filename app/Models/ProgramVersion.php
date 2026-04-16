<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramVersion extends Model
{
    protected $table = 'program_versions';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'program_exercise_id',
        'user_program_id',
        'name',
        'level',
        'source_type',
        'source_id',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function exercises()
    {
        return $this->hasMany(ProgramExercises::class, 'program_version_id');
    }

    public function adjustments()
    {
        return $this->hasMany(InjuryProgramAdjustments::class, 'program_version_id');
    }

    public function userProgram()
    {
        return $this->belongsTo(UserProgram::class, 'user_program_id');
    }

    public function injury()
    {
        return $this->belongsTo(UserInjuries::class, 'source_id')
            ->where('source_type', 'injury');
    }
}