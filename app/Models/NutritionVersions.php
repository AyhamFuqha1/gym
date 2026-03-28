<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionVersions extends Model
{
    protected $table = 'nutrition_versions';

    protected $fillable = [
        'user_nutrition_plan_id',
        'daily_calories',
        'is_active',
    ];

    public function nutritions()
    {
        return $this->belongsToMany(
            Nutrition::class,
            'nutrition_food_items', 
            'nutrition_version_id',  
            'nutrition_id'
        )
            ->withPivot('quantity', 'meal_type')
            ->withTimestamps();
    }

    public function NutritionPlans()
    {
        return $this->hasMany(UserNutritionPlans::class, "user_nutrition_plan_id");
    }
}