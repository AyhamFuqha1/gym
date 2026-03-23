<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'duration_days',
        'price',
        'is_active',
        'user_id'
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
