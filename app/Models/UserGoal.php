<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGoal extends Model
{
    public $timestamps = false;
    protected $table = 'user_goals';

    protected $fillable = [
        'user_id',
        'goal_type',
        'target_weight',
    ];

    protected $casts = [
        'target_weight' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
