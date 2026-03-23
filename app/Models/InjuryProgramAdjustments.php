<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InjuryProgramAdjustments extends Model
{
    protected $table = 'injury_program_adjustments';

    protected $fillable = [
        'injury_id',
        'program_version_id',
        'old_exercise_id',
        'new_exercise_id',
        'action',
        'old_value',
        'new_value',
        'notes',
        'applied_by',
    ];
    public $timestamps = false;

    public function oldexercise()
    {
        return $this->belongsTo(Exercises::class, 'old_exercise_id');
    }
    public function newexercise()
    {
        return $this->belongsTo(Exercises::class, 'new_exercise_id');
    }
    public function programVersion()
    {
        return $this->belongsTo(ProgramVersion::class, 'program_version_id');
    }
    public function injury()
    {
        return $this->belongsTo(UserInjuries::class, 'injury_id');
    }
}
