<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'created_by',
        'plan_id',
        'discount',
        'start_date',
        'end_date',
        'status',
        'frozen_at',
        'resumed_at',
        'frozen_remaining_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'frozen_at' => 'datetime',
        'resumed_at' => 'datetime',
        'frozen_remaining_days' => 'integer',
    ];

    protected $appends = [
        'remaining_days',
    ];

    public function getRemainingDaysAttribute(): int
    {
        if ($this->status === 'frozen') {
            return max((int) ($this->frozen_remaining_days ?? 0), 0);
        }

        if ($this->status !== 'active' || !$this->end_date) {
            return 0;
        }

        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date)->startOfDay();

        if ($endDate->lte($today)) {
            return 0;
        }

        return (int) $today->diffInDays($endDate);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
