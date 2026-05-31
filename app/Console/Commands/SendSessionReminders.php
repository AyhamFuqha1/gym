<?php

namespace App\Console\Commands;

use App\Models\CoachSession;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSessionReminders extends Command
{
    private const ACTIVE_BOOKING_STATUSES = ['booked', 'confirmed', 'active'];

    protected $signature = 'notifications:send-session-reminders {--minutes=60 : Reminder window in minutes}';

    protected $description = 'Send reminders for booked sessions starting soon.';

    public function handle(NotificationService $notificationService): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $now = now();
        $target = $now->copy()->addMinutes($minutes);

        $sessions = CoachSession::query()
            ->with(['bookings' => function ($query) {
                $query->whereIn('status', self::ACTIVE_BOOKING_STATUSES);
            }])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('session_date')
            ->whereBetween('session_date', [
                $now->toDateString(),
                $target->toDateString(),
            ])
            ->whereHas('bookings', function ($query) {
                $query->whereIn('status', self::ACTIVE_BOOKING_STATUSES);
            })
            ->get();

        $sessionsScanned = $sessions->count();
        $notificationsCreated = 0;
        $duplicatesSkipped = 0;

        foreach ($sessions as $session) {
            $sessionStart = $this->sessionStart($session);

            if (!$sessionStart || $sessionStart->lt($now) || $sessionStart->gt($target)) {
                continue;
            }

            foreach ($session->bookings as $booking) {
                try {
                    $notification = $notificationService->notifyUser((int) $booking->user_id, [
                        'type' => 'session_reminder',
                        'title' => 'Upcoming training session',
                        'body' => $this->sessionReminderBody($session),
                        'entity_type' => 'session',
                        'entity_id' => $session->id,
                        'priority' => 'high',
                        'channels' => ['in_app', 'push'],
                        'data' => array_filter([
                            'screen' => 'Sessions',
                            'session_id' => $session->id,
                            'type' => 'session_reminder',
                            'entity_type' => 'session',
                            'entity_id' => $session->id,
                            'session_date' => $this->sessionDateForPayload($session),
                            'start_time' => $this->timeForPayload($session->start_time),
                            'end_time' => $this->timeForPayload($session->end_time),
                            'coach_id' => $session->coach_id,
                        ], fn ($value) => $value !== null),
                        'dedupe_key' => "session_reminder:{$session->id}:{$booking->user_id}",
                    ]);

                    if ($notification->wasRecentlyCreated) {
                        $notificationsCreated++;
                    } else {
                        $duplicatesSkipped++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to create session reminder notification.', [
                        'session_id' => $session->id,
                        'recipient_user_id' => $booking->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $summary = [
            'window_minutes' => $minutes,
            'window_start' => $now->toDateTimeString(),
            'window_end' => $target->toDateTimeString(),
            'sessions_scanned' => $sessionsScanned,
            'notifications_created' => $notificationsCreated,
            'duplicates_skipped' => $duplicatesSkipped,
        ];

        Log::info('Session reminder notification command finished.', $summary);

        $this->info(
            "Session reminders checked {$sessionsScanned} session(s), created {$notificationsCreated} notification(s), skipped {$duplicatesSkipped} duplicate(s)."
        );

        return Command::SUCCESS;
    }

    private function sessionStart(CoachSession $session): ?Carbon
    {
        $sessionDate = $this->sessionDateForPayload($session);
        $startTime = $this->timeForPayload($session->start_time);

        if (!$sessionDate || !$startTime) {
            return null;
        }

        return Carbon::parse("{$sessionDate} {$startTime}");
    }

    private function sessionReminderBody(CoachSession $session): string
    {
        $sessionDate = $this->sessionDateForPayload($session);
        $startTime = $this->timeForPayload($session->start_time);

        if ($sessionDate && $startTime) {
            return "Your session on {$sessionDate} starts at {$startTime}.";
        }

        if ($startTime) {
            return "Your session starts at {$startTime}.";
        }

        return 'Your training session starts soon.';
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
}
