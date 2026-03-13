<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
     protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'age',
        'height',
        'weight',
        'gender',
        'activity_level',
        'preferences',
        'food_allergies',
        'medical_conditions'
    ];
        public $timestamps = false;
}
