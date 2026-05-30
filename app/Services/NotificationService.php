<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public function notifyUser(int $recipientUserId, array $payload): Notification
    {
        $attributes = $this->buildAttributes($recipientUserId, $payload);
        $dedupeKey = $attributes['dedupe_key'] ?? null;

        if ($dedupeKey) {
            $notification = Notification::firstOrCreate(
                ['dedupe_key' => $dedupeKey],
                $attributes
            );
        } else {
            $notification = Notification::create($attributes);
        }

        if ($notification->wasRecentlyCreated && $this->shouldSendPush($notification)) {
            try {
                SendPushNotificationJob::dispatch($notification->id)->afterCommit();
            } catch (\Throwable $e) {
                Log::warning('Could not dispatch push notification job.', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }

    public function notifyUsers(array $recipientUserIds, array $payload): array
    {
        $notifications = [];

        foreach ($this->normalizeUserIds($recipientUserIds) as $recipientUserId) {
            $recipientPayload = $payload;

            if (!empty($payload['dedupe_key'])) {
                $recipientPayload['dedupe_key'] = "{$payload['dedupe_key']}:recipient:{$recipientUserId}";
            }

            $notifications[] = $this->notifyUser($recipientUserId, $recipientPayload);
        }

        return $notifications;
    }

    public function notifyRoleNames(array $roleNames, array $payload): array
    {
        $normalizedRoleNames = collect($roleNames)
            ->filter(fn ($roleName) => is_string($roleName) && trim($roleName) !== '')
            ->map(fn (string $roleName) => trim($roleName))
            ->unique()
            ->values()
            ->all();

        if (!$normalizedRoleNames) {
            return [];
        }

        $recipientUserIds = User::query()
            ->whereHas('role', function (Builder $query) use ($normalizedRoleNames) {
                $query->whereIn('name', $normalizedRoleNames);
            })
            ->pluck('id')
            ->all();

        return $this->notifyUsers($recipientUserIds, $payload);
    }

    public function markAsRead(int $notificationId, int $userId): Notification
    {
        $notification = $this->queryForUser($userId)->findOrFail($notificationId);

        $updates = [
            'read_at' => $notification->read_at ?? now(),
        ];

        if (Schema::hasColumn('notifications', 'is_read')) {
            $updates['is_read'] = true;
        }

        $notification->update($updates);

        return $notification->refresh();
    }

    public function markAllAsRead(int $userId): int
    {
        $updates = [
            'read_at' => now(),
        ];

        if (Schema::hasColumn('notifications', 'is_read')) {
            $updates['is_read'] = true;
        }

        return $this->queryForUser($userId)
            ->where(function (Builder $query) {
                $query->whereNull('read_at');

                if (Schema::hasColumn('notifications', 'is_read')) {
                    $query->orWhere('is_read', false);
                }
            })
            ->update($updates);
    }

    public function unreadCount(int $userId): int
    {
        return $this->queryForUser($userId)
            ->where(function (Builder $query) {
                $query->whereNull('read_at');

                if (Schema::hasColumn('notifications', 'is_read')) {
                    $query->orWhere('is_read', false);
                }
            })
            ->count();
    }

    private function buildAttributes(int $recipientUserId, array $payload): array
    {
        $channels = $payload['channels'] ?? ['in_app'];

        if (is_string($channels)) {
            $channels = [$channels];
        }

        if (!is_array($channels) || !$channels) {
            $channels = ['in_app'];
        }

        $attributes = [
            'recipient_user_id' => $recipientUserId,
            'actor_user_id' => $payload['actor_user_id'] ?? null,
            'type' => $payload['type'] ?? null,
            'title' => $payload['title'] ?? 'Notification',
            'body' => $payload['body'] ?? $payload['message'] ?? null,
            'entity_type' => $payload['entity_type'] ?? null,
            'entity_id' => $payload['entity_id'] ?? null,
            'data' => $payload['data'] ?? null,
            'priority' => $payload['priority'] ?? 'normal',
            'channels' => array_values(array_unique($channels)),
            'dedupe_key' => $payload['dedupe_key'] ?? null,
        ];

        if (Schema::hasColumn('notifications', 'user_id')) {
            $attributes['user_id'] = $recipientUserId;
        }

        if (Schema::hasColumn('notifications', 'message')) {
            $attributes['message'] = $attributes['body'] ?? $attributes['title'];
        }

        if (Schema::hasColumn('notifications', 'is_read')) {
            $attributes['is_read'] = false;
        }

        return $attributes;
    }

    private function shouldSendPush(Notification $notification): bool
    {
        $channels = $notification->channels ?? [];

        if (is_string($channels)) {
            $channels = [$channels];
        }

        return in_array('push', array_map('strtolower', $channels), true);
    }

    private function queryForUser(int $userId): Builder
    {
        return Notification::query()
            ->where(function (Builder $query) use ($userId) {
                $query->where('recipient_user_id', $userId);

                if (Schema::hasColumn('notifications', 'user_id')) {
                    $query->orWhere('user_id', $userId);
                }
            });
    }

    private function normalizeUserIds(array $userIds): array
    {
        return collect($userIds)
            ->map(fn ($userId) => is_numeric($userId) ? (int) $userId : null)
            ->filter(fn ($userId) => $userId !== null && $userId > 0)
            ->unique()
            ->values()
            ->all();
    }
}
