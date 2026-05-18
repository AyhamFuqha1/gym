<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachSession extends Model
{
    protected $fillable = [
        'coach_id',
        'day_of_week',
        'session_date',
        'start_time',
        'end_time',
        'capacity',
        'booked_count',
        'status',
        'is_recurring',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'session_date' => 'date',
    ];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'session_id');
    }

    public function isFull(): bool
    {
        return $this->booked_count >= $this->capacity;
    }

    public function incrementBooking(): void
    {
        $this->booked_count++;

        if ($this->booked_count >= $this->capacity) {
            $this->status = 'full';
        }

        $this->save();
    }

    public function decrementBooking(): void
    {
        $this->booked_count = max(0, $this->booked_count - 1);

        if ($this->status === 'full' && $this->booked_count < $this->capacity) {
            $this->status = 'available';
        }

        $this->save();
    }
}
