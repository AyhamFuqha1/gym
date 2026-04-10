<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionVersions extends Model
{
    public $timestamps = false;
    protected $table = 'nutrition_versions';

    protected $fillable = [
        'user_nutrition_plan_id',
        'daily_calories',
        'daily_protein',
        'daily_carbs',
        'daily_fat',
        'reason',
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

    public function foodItems()
    {
        return $this->hasMany(NutritionFoodItems::class, 'nutrition_version_id');
    }

    public function userNutritionPlan()
    {
        return $this->belongsTo(UserNutritionPlans::class, 'user_nutrition_plan_id');
    }
}