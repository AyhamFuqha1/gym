<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->query('per_page', 15), 50);

        $notifications = Notification::query()
            ->where('recipient_user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn (Notification $notification) => $this->formatNotification($notification));

        return response()->json([
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'unread_count' => $this->notificationService->unreadCount($request->user()->id),
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($id, $request->user()->id);

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $this->formatNotification($notification),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updatedCount = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'message' => 'Notifications marked as read',
            'data' => [
                'updated_count' => $updatedCount,
            ],
        ]);
    }

    private function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'entity_type' => $notification->entity_type,
            'entity_id' => $notification->entity_id,
            'data' => $notification->data,
            'priority' => $notification->priority,
            'channels' => $notification->channels,
            'read_at' => optional($notification->read_at)->toDateTimeString(),
            'created_at' => optional($notification->created_at)->toDateTimeString(),
        ];
    }
}
