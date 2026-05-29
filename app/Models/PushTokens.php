<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushTokens extends Model
{
    protected $table = 'push_tokens';

    protected $fillable = [
        'user_id',
        'token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
