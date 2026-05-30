<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'recipient_user_id',
        'actor_user_id',
        'type',
        'title',
        'message',
        'body',
        'entity_type',
        'entity_id',
        'data',
        'priority',
        'channels',
        'is_read',
        'read_at',
        'sent_at',
        'dedupe_key',
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
