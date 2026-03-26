<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralNutrition extends Model
{
    protected $table = 'general_nutrition';

    protected $fillable = [
        'category_name',
        'icon',
        'description',
    ];

    public $timestamps = true;

    public function nutritions()
    {
        return $this->hasMany(Nutrition::class, 'general_nutrition_id');
    }
}
