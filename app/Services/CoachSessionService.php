<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CoachSession;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class CoachSessionService
{
    public function getCoaches()
    {
        return User::query()
            ->whereHas('role', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['coach']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $coach) => [
                'id' => $coach->id,
                'name' => $coach->name,
                'email' => $coach->email,
            ])
            ->values();
    }

    public function show(int $id): CoachSession
    {
        return CoachSession::with(['coach', 'bookings.user'])->findOrFail($id);
    }

    public function store(array $data): CoachSession
    {
        $data['booked_count'] = 0;
        $data = $this->applyDerivedSessionStatus($data);
        $this->ensureNoOverlap($data);

        return CoachSession::create($data);
    }

    public function update(array $data, int $id): CoachSession
    {
        $coachSession = CoachSession::findOrFail($id);
        unset($data['booked_count']);
        $data = $this->applyDerivedSessionStatus($data, $coachSession);

        $this->ensureNoOverlap(array_merge($coachSession->toArray(), $data), $id);

        $coachSession->update($data);

        if (($data['status'] ?? null) === 'cancelled') {
            $coachSession->bookings()->update(['status' => 'cancelled']);
        }

        return $coachSession->fresh(['coach', 'bookings.user']);
    }

    public function destroy(int $id): bool
    {
        $coachSession = CoachSession::findOrFail($id);

        return (bool) $coachSession->delete();
    }

    public function showSessions()
    {
        return CoachSession::with('coach')
            ->where('status', 'available')
            ->whereColumn('booked_count', '<', 'capacity')
            ->where(function ($query) {
                $query->whereNull('session_date')
                    ->orWhereDate('session_date', '>=', Carbon::today());
            })
            ->orderBy('session_date')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function getMySessions()
    {
        return Booking::with(['session.coach'])
            ->where('user_id', auth()->id())
            ->get();
    }

    public function bookSession(int $sessionId, ?int $userId = null): CoachSession
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            throw new Exception('Authenticated user is required to book a session');
        }

        return DB::transaction(function () use ($sessionId, $userId) {
            $session = CoachSession::lockForUpdate()->findOrFail($sessionId);

            if ($session->status === 'cancelled') {
                throw new Exception('Session is cancelled');
            }

            if ($session->isFull()) {
                throw new Exception('Session is full');
            }

            $booking = Booking::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->first();

            if ($booking && $booking->status === 'booked') {
                throw new Exception('Already booked');
            }

            if ($booking) {
                $booking->update(['status' => 'booked']);
            } else {
                $session->bookings()->create([
                    'user_id' => $userId,
                    'status' => 'booked',
                ]);
            }

            $session->incrementBooking();

            return $session->fresh(['coach', 'bookings.user']);
        });
    }

    public function cancelSession(int $sessionId): CoachSession
    {
        $userId = auth()->id();

        return DB::transaction(function () use ($sessionId, $userId) {
            $session = CoachSession::lockForUpdate()->findOrFail($sessionId);
            $booking = $session->bookings()
                ->where('user_id', $userId)
                ->where('status', 'booked')
                ->firstOrFail();

            $booking->update(['status' => 'cancelled']);
            $session->decrementBooking();

            return $session->fresh(['coach', 'bookings.user']);
        });
    }

    public function getAllSessionsForAdmin()
    {
        return CoachSession::with(['coach', 'bookings.user'])->get();
    }

    public function getSessionDetailsForAdmin(int $sessionId): CoachSession
    {
        return CoachSession::with(['coach', 'bookings.user'])->findOrFail($sessionId);
    }

    public function adminCancelSession(int $sessionId): CoachSession
    {
        return $this->cancelWholeSession($sessionId);
    }

    public function adminRestoreSession(int $sessionId): CoachSession
    {
        $session = CoachSession::findOrFail($sessionId);
        $session->update($this->applyDerivedSessionStatus(['status' => 'available'], $session));

        return $session->fresh(['coach', 'bookings.user']);
    }

    public function cancelWholeSession(int $sessionId): CoachSession
    {
        return DB::transaction(function () use ($sessionId) {
            $session = CoachSession::lockForUpdate()->findOrFail($sessionId);

            $session->update(['status' => 'cancelled']);

            return $session->fresh(['coach', 'bookings.user']);
        });
    }

    private function applyDerivedSessionStatus(array $data, ?CoachSession $session = null): array
    {
        $requestedStatus = $data['status'] ?? $session?->status ?? 'available';

        if ($requestedStatus === 'cancelled') {
            $data['status'] = 'cancelled';
            return $data;
        }

        $bookedCount = (int) ($data['booked_count'] ?? $session?->booked_count ?? 0);
        $capacity = (int) ($data['capacity'] ?? $session?->capacity ?? 0);

        $data['status'] = $capacity > 0 && $bookedCount >= $capacity
            ? 'full'
            : 'available';

        return $data;
    }

    private function ensureNoOverlap(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['coach_id']) || empty($data['start_time']) || empty($data['end_time'])) {
            return;
        }

        $query = CoachSession::where('coach_id', $data['coach_id'])
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $query->where(function ($query) use ($data) {
            if (!empty($data['session_date'])) {
                $query->whereDate('session_date', $data['session_date']);
            }

            if (array_key_exists('day_of_week', $data) && $data['day_of_week'] !== null) {
                $query->orWhere('day_of_week', $data['day_of_week']);
            }
        });

        if ($query->exists()) {
            throw new Exception('Session already exists');
        }
    }
}
