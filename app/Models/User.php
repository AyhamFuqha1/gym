<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public $timestamps = false;
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function injuries()
    {
        return $this->hasMany(UserInjuries::class, 'user_id');
    }
    public function injuriesActive()
    {
        return $this->hasMany(UserInjuries::class, 'user_id')->where('status', 'active');
    }
    public function feedback()
    {
        return $this->hasOne(feedback::class, 'user_id');
    }
    public function Paln()
    {
        return $this->hasOne(Plan::class, 'user_id');
    }
    public function subscription()
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }
    public function plan()
    {
        return $this->hasOne(Plan::class, 'user_id');
    }
    public function Profile()
    {
        return $this->hasOne(UserProfiles::class, 'user_id');
    }

    public function goals()
    {
        return $this->hasOne(UserGoal::class, 'user_id');
    }
    public function likedFoods()
    {
        return $this->belongsToMany(Food::class)
            ->withPivot('type')
            ->wherePivot('type', '=', 'like');
    }

    public function dislikedFoods()
    {
        return $this->belongsToMany(Food::class)
            ->withPivot('type')
            ->wherePivot('type', '=', 'dislike');
    }
    public function UserNutritionPlan()
    {
        return $this->hasMany(UserNutritionPlans::class, 'user_id');
    }
    public function UserNutritionPlanِActive()
    {
        return $this->hasMany(UserNutritionPlans::class, 'user_id')->where('active', 1);
    }


}
