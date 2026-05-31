<?php

namespace App\Services;

use App\Jobs\SendNewEmail;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewsServices
{
    public function __construct(private NotificationService $notificationService)
    {
        //
    }

    public function getPaginatedNews($perPage = 10)
    {
        $paginated = News::with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function (News $news) {
            return $this->formatNews($news);
        });

        return $paginated;
    }

    public function getNewsStats()
    {
        $publishedCount = News::where('status', 'public')->count();
        $draftCount = News::where('status', 'draft')->count();
        $totalNews = (int) News::where('status', 'deleted')->count();

        return [
            'published' => $publishedCount,
            'drafts' => $draftCount,
            'total_views' => $totalNews,
        ];
    }

    public function store($data, ?int $actorUserId = null)
    {
        if (($data['status'] ?? null) === 'public' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (!empty($data['expires_at'])) {
            $data['expires_at'] = Carbon::parse($data['expires_at'])->format('Y-m-d H:i:s');
        }

        $emails = $data['emails'] ?? [];
        unset($data['emails']);

        return DB::transaction(function () use ($data, $emails, $actorUserId) {
            $news = News::create($data);

            foreach ($emails as $email) {
                SendNewEmail::dispatch($email, $news->title, $news->content);
            }

            if ($news->status === 'public') {
                $this->queueNewsPublishedNotifications($news->id, $actorUserId);
            }

            return $news;
        });
    }

    public function destroy($id)
    {
        $news = News::findOrFail($id);

        if ($news->status === 'deleted') {
            return $news->delete();
        }

        return $news->update(['status' => 'deleted']);
    }

    public function update($id, array $data, ?int $actorUserId = null)
    {
        $news = News::findOrFail($id);
        $oldStatus = $news->status;

        $newStatus = $data['status'] ?? null;

        if (
            $newStatus === 'public' &&
            !$news->published_at &&
            empty($data['published_at'])
        ) {
            $data['published_at'] = now();
        }

        if (!empty($data['expires_at'])) {
            $data['expires_at'] = Carbon::parse($data['expires_at'])->format('Y-m-d H:i:s');
        }

        unset($data['emails']);

        $news->update($data);
        $news->load('user');

        if ($oldStatus !== 'public' && $news->status === 'public') {
            $this->queueNewsPublishedNotifications($news->id, $actorUserId);
        }

        return $this->formatNews($news);
    }

    public function show($id)
    {
        $news = News::with('user')->findOrFail($id);
        return $this->formatNews($news);
    }

    private function formatNews(News $news)
    {
        $expiresAt = $news->expires_at ? Carbon::parse($news->expires_at) : null;
        $isExpired = $expiresAt ? now()->greaterThan($expiresAt) : false;

        $remainingDays = null;
        if ($expiresAt && !$isExpired) {
            $remainingDays = now()->diffInDays($expiresAt, false);
        }

        return [
            'id' => $news->id,
            'title' => $news->title,
            'content' => $news->content,
            'status' => $news->status,
            'author_name' => optional($news->user)->name,
            'formatted_date' => optional($news->created_at)->format('M j, Y'),
            'published_at' => optional($news->published_at)?->toDateTimeString(),
            'expires_at' => optional($news->expires_at)?->toDateTimeString(),
            'is_expired' => $isExpired,
            'remaining_days' => $remainingDays,
        ];
    }

    public function getPublicNews($perPage = 10)
    {
        $now = now();

        $paginated = News::with('user')
            ->where('status', 'public')
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function (News $news) {
            return $this->formatNews($news);
        });

        return $paginated;
    }

    private function queueNewsPublishedNotifications(int $newsId, ?int $actorUserId = null): void
    {
        DB::afterCommit(function () use ($newsId, $actorUserId) {
            try {
                $news = News::find($newsId);

                if (!$news || $news->status !== 'public') {
                    return;
                }

                foreach ($this->notificationService->eligibleMemberUserIds() as $recipientUserId) {
                    $this->notifyNewsPublished($news, $recipientUserId, $actorUserId);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to create news published notifications.', [
                    'news_id' => $newsId,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    private function notifyNewsPublished(News $news, int $recipientUserId, ?int $actorUserId = null): void
    {
        try {
            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => $actorUserId,
                'type' => 'news_published',
                'title' => 'New FitMind news',
                'body' => $news->title,
                'entity_type' => 'news',
                'entity_id' => $news->id,
                'priority' => 'normal',
                'channels' => ['in_app', 'push'],
                'data' => [
                    'news_id' => $news->id,
                    'title' => $news->title,
                    'status' => $news->status,
                    'screen' => 'NewsDetails',
                ],
                'dedupe_key' => "news_published:{$news->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create news notification for recipient.', [
                'news_id' => $news->id,
                'recipient_user_id' => $recipientUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
