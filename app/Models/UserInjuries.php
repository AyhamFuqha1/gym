<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInjuries extends Model
{
    protected $table = 'user_injuries';

    protected $fillable = [
        'user_id',
        'injury_type',
        'severity',
        'notes',
        'status',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function adjustments()
    {
        return $this->hasMany(InjuryProgramAdjustments::class, 'injury_id');
    }
    
}


