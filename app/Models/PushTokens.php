<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushTokens extends Model
{
    protected $table = 'push_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'provider',
        'platform',
        'device_id',
        'app_version',
        'is_active',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
