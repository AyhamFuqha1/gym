<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CoachSession;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoachSessionService
{
    public function __construct(private NotificationService $notificationService)
    {
        //
    }

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

        $session = CoachSession::create($data);

        $this->queueSessionCreatedNotifications($session);

        return $session;
    }

    public function update(array $data, int $id): CoachSession
    {
        $coachSession = CoachSession::findOrFail($id);
        $oldStatus = $coachSession->status;
        unset($data['booked_count']);
        $data = $this->applyDerivedSessionStatus($data, $coachSession);
        $shouldNotifyCancellation = $oldStatus !== 'cancelled' && ($data['status'] ?? null) === 'cancelled';
        $cancelledRecipientUserIds = $shouldNotifyCancellation
            ? $this->bookedSessionNotificationRecipients($coachSession->id)
            : [];

        $this->ensureNoOverlap(array_merge($coachSession->toArray(), $data), $id);

        $coachSession->update($data);

        if (($data['status'] ?? null) === 'cancelled') {
            $coachSession->bookings()->update(['status' => 'cancelled']);
        }

        if ($shouldNotifyCancellation) {
            $this->queueSessionCancelledNotifications($coachSession->id, $cancelledRecipientUserIds);
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
                $booking->refresh();
            } else {
                $booking = $session->bookings()->create([
                    'user_id' => $userId,
                    'status' => 'booked',
                ]);
            }

            $session->incrementBooking();
            $this->queueBookingConfirmedNotification($booking, $session, $userId);

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
            $booking->refresh();
            $session->decrementBooking();
            $this->queueBookingCancelledNotification($booking, $session, $userId);

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
            $wasAlreadyCancelled = $session->status === 'cancelled';
            $recipientUserIds = $wasAlreadyCancelled
                ? []
                : $this->bookedSessionNotificationRecipients($session->id);

            $session->update(['status' => 'cancelled']);

            $freshSession = $session->fresh(['coach', 'bookings.user']);

            if (!$wasAlreadyCancelled) {
                $this->queueSessionCancelledNotifications($session->id, $recipientUserIds);
            }

            return $freshSession;
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

    private function queueSessionCreatedNotifications(CoachSession $session): void
    {
        try {
            $recipientUserIds = $this->notificationService->eligibleMemberUserIds();
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve session created notification recipients.', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        foreach ($recipientUserIds as $recipientUserId) {
            $this->notifySessionCreated($session, $recipientUserId);
        }
    }

    private function notifySessionCreated(CoachSession $session, int $recipientUserId): void
    {
        try {
            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => auth()->id(),
                'type' => 'session_created',
                'title' => 'New training session available',
                'body' => $this->sessionCreatedBody($session),
                'entity_type' => 'session',
                'entity_id' => $session->id,
                'priority' => 'normal',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'Sessions',
                    'session_id' => $session->id,
                    'type' => 'session_created',
                    'entity_type' => 'session',
                    'entity_id' => $session->id,
                    'session_date' => $this->sessionDateForPayload($session),
                    'start_time' => $this->timeForPayload($session->start_time),
                    'coach_id' => $session->coach_id,
                ], fn ($value) => $value !== null),
                'dedupe_key' => "session_created:{$session->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create session created notification for recipient.', [
                'session_id' => $session->id,
                'recipient_user_id' => $recipientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function queueSessionCancelledNotifications(int $sessionId, array $recipientUserIds): void
    {
        if (!$recipientUserIds) {
            return;
        }

        $callback = function () use ($sessionId, $recipientUserIds) {
            try {
                $session = CoachSession::find($sessionId);

                if (!$session || $session->status !== 'cancelled') {
                    return;
                }

                foreach ($recipientUserIds as $recipientUserId) {
                    $this->notifySessionCancelled($session, (int) $recipientUserId);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to create session cancelled notifications.', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
            return;
        }

        $callback();
    }

    private function notifySessionCancelled(CoachSession $session, int $recipientUserId): void
    {
        try {
            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => auth()->id(),
                'type' => 'session_cancelled',
                'title' => 'Training session cancelled',
                'body' => $this->sessionCancelledBody($session),
                'entity_type' => 'session',
                'entity_id' => $session->id,
                'priority' => 'high',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'Sessions',
                    'session_id' => $session->id,
                    'type' => 'session_cancelled',
                    'entity_type' => 'session',
                    'entity_id' => $session->id,
                    'session_date' => $this->sessionDateForPayload($session),
                    'start_time' => $this->timeForPayload($session->start_time),
                    'end_time' => $this->timeForPayload($session->end_time),
                    'coach_id' => $session->coach_id,
                ], fn ($value) => $value !== null),
                'dedupe_key' => "session_cancelled:{$session->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create session cancelled notification for recipient.', [
                'session_id' => $session->id,
                'recipient_user_id' => $recipientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function queueBookingConfirmedNotification(Booking $booking, CoachSession $session, int $recipientUserId): void
    {
        $bookingId = $booking->id;
        $sessionId = $session->id;
        $actorUserId = auth()->id();

        $callback = function () use ($bookingId, $sessionId, $recipientUserId, $actorUserId) {
            try {
                $booking = Booking::with('session')->find($bookingId);

                if (
                    !$booking ||
                    $booking->status !== 'booked' ||
                    (int) $booking->user_id !== $recipientUserId
                ) {
                    return;
                }

                $session = $booking->session ?? CoachSession::find($sessionId);

                if (!$session) {
                    return;
                }

                $this->notifyBookingConfirmed($booking, $session, $recipientUserId, $actorUserId);
            } catch (\Throwable $e) {
                Log::warning('Failed to create booking confirmed notification.', [
                    'booking_id' => $bookingId,
                    'session_id' => $sessionId,
                    'recipient_user_id' => $recipientUserId,
                    'error' => $e->getMessage(),
                ]);
            }
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
            return;
        }

        $callback();
    }

    private function notifyBookingConfirmed(
        Booking $booking,
        CoachSession $session,
        int $recipientUserId,
        ?int $actorUserId = null
    ): void {
        try {
            $entityId = $booking->id ?: $session->id;

            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => $actorUserId,
                'type' => 'booking_confirmed',
                'title' => 'Session booking confirmed',
                'body' => $this->bookingConfirmedBody($session),
                'entity_type' => 'booking',
                'entity_id' => $entityId,
                'priority' => 'normal',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'Sessions',
                    'booking_id' => $booking->id,
                    'session_id' => $session->id,
                    'type' => 'booking_confirmed',
                    'entity_type' => 'booking',
                    'entity_id' => $entityId,
                    'session_date' => $this->sessionDateForPayload($session),
                    'start_time' => $this->timeForPayload($session->start_time),
                    'end_time' => $this->timeForPayload($session->end_time),
                    'coach_id' => $session->coach_id,
                ], fn ($value) => $value !== null),
                'dedupe_key' => $booking->id
                    ? "booking_confirmed:{$booking->id}:{$recipientUserId}"
                    : "booking_confirmed:{$session->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create booking confirmed notification for recipient.', [
                'booking_id' => $booking->id,
                'session_id' => $session->id,
                'recipient_user_id' => $recipientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function queueBookingCancelledNotification(Booking $booking, CoachSession $session, int $recipientUserId): void
    {
        $bookingId = $booking->id;
        $sessionId = $session->id;
        $actorUserId = auth()->id();

        $callback = function () use ($bookingId, $sessionId, $recipientUserId, $actorUserId) {
            try {
                $booking = Booking::with('session')->find($bookingId);

                if (
                    !$booking ||
                    $booking->status !== 'cancelled' ||
                    (int) $booking->user_id !== $recipientUserId
                ) {
                    return;
                }

                $session = $booking->session ?? CoachSession::find($sessionId);

                if (!$session) {
                    return;
                }

                $this->notifyBookingCancelled($booking, $session, $recipientUserId, $actorUserId);
            } catch (\Throwable $e) {
                Log::warning('Failed to create booking cancelled notification.', [
                    'booking_id' => $bookingId,
                    'session_id' => $sessionId,
                    'recipient_user_id' => $recipientUserId,
                    'error' => $e->getMessage(),
                ]);
            }
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
            return;
        }

        $callback();
    }

    private function notifyBookingCancelled(
        Booking $booking,
        CoachSession $session,
        int $recipientUserId,
        ?int $actorUserId = null
    ): void {
        try {
            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => $actorUserId,
                'type' => 'booking_cancelled',
                'title' => 'Session booking cancelled',
                'body' => $this->bookingCancelledBody($session),
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'priority' => 'normal',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'Sessions',
                    'booking_id' => $booking->id,
                    'session_id' => $session->id,
                    'type' => 'booking_cancelled',
                    'entity_type' => 'booking',
                    'entity_id' => $booking->id,
                    'session_date' => $this->sessionDateForPayload($session),
                    'start_time' => $this->timeForPayload($session->start_time),
                    'end_time' => $this->timeForPayload($session->end_time),
                    'coach_id' => $session->coach_id,
                ], fn ($value) => $value !== null),
                'dedupe_key' => "booking_cancelled:{$booking->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create booking cancelled notification for recipient.', [
                'booking_id' => $booking->id,
                'session_id' => $session->id,
                'recipient_user_id' => $recipientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sessionCreatedBody(CoachSession $session): string
    {
        $name = $session->getAttribute('title') ?: $session->getAttribute('name');

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        $sessionDate = $this->sessionDateForPayload($session);
        $startTime = $this->timeForPayload($session->start_time);

        if ($sessionDate && $startTime) {
            return "New training session on {$sessionDate} at {$startTime}.";
        }

        if ($sessionDate) {
            return "New training session on {$sessionDate}.";
        }

        if ($startTime) {
            return "New training session at {$startTime}.";
        }

        return 'A new training session is available.';
    }

    private function sessionCancelledBody(CoachSession $session): string
    {
        $sessionDate = $this->sessionDateForPayload($session);
        $startTime = $this->timeForPayload($session->start_time);
        $endTime = $this->timeForPayload($session->end_time);

        if ($sessionDate && $startTime && $endTime) {
            return "Training session on {$sessionDate} from {$startTime} to {$endTime} has been cancelled.";
        }

        if ($sessionDate && $startTime) {
            return "Training session on {$sessionDate} at {$startTime} has been cancelled.";
        }

        if ($sessionDate) {
            return "Training session on {$sessionDate} has been cancelled.";
        }

        if ($startTime) {
            return "Training session at {$startTime} has been cancelled.";
        }

        return 'A training session has been cancelled.';
    }

    private function bookingConfirmedBody(CoachSession $session): string
    {
        $sessionDate = $this->sessionDateForPayload($session);
        $startTime = $this->timeForPayload($session->start_time);
        $endTime = $this->timeForPayload($session->end_time);

        if ($sessionDate && $startTime && $endTime) {
            return "Your training session on {$sessionDate} from {$startTime} to {$endTime} is booked.";
        }

        if ($sessionDate && $startTime) {
            return "Your training session on {$sessionDate} at {$startTime} is booked.";
        }

        if ($sessionDate) {
            return "Your training session on {$sessionDate} is booked.";
        }

        if ($startTime) {
            return "Your training session at {$startTime} is booked.";
        }

        return 'Your training session is booked.';
    }

    private function bookingCancelledBody(CoachSession $session): string
    {
        $sessionDate = $this->sessionDateForPayload($session);
        $startTime = $this->timeForPayload($session->start_time);
        $endTime = $this->timeForPayload($session->end_time);

        if ($sessionDate && $startTime && $endTime) {
            return "Your training session on {$sessionDate} from {$startTime} to {$endTime} has been cancelled.";
        }

        if ($sessionDate && $startTime) {
            return "Your training session on {$sessionDate} at {$startTime} has been cancelled.";
        }

        if ($sessionDate) {
            return "Your training session on {$sessionDate} has been cancelled.";
        }

        if ($startTime) {
            return "Your training session at {$startTime} has been cancelled.";
        }

        return 'Your training session booking has been cancelled.';
    }

    private function sessionDateForPayload(CoachSession $session): ?string
    {
        if (!$session->session_date) {
            return null;
        }

        if ($session->session_date instanceof \DateTimeInterface) {
            return $session->session_date->format('Y-m-d');
        }

        return (string) $session->session_date;
    }

    private function timeForPayload($time): ?string
    {
        if (!$time) {
            return null;
        }

        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        $time = trim((string) $time);

        if ($time === '') {
            return null;
        }

        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    private function bookedSessionNotificationRecipients(int $sessionId): array
    {
        try {
            return $this->notificationService->bookedSessionUserIds($sessionId);
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve session cancelled notification recipients.', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
