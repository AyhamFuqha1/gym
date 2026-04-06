<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNutritionPlans extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'user_nutrition_plans';

    protected $fillable = [
        'name',
        'user_id',
        'start_date',
        'end_date',
        'goal_type',
        'active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function nutritionVersions()
    {
        return $this->hasMany(NutritionVersions::class, 'user_nutrition_plan_id');
    }


    public function activeVersion()
    {
        return $this->hasOne(NutritionVersions::class, 'user_nutrition_plan_id')
            ->where('is_active', true);
    }
}