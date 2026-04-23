<?php

namespace App\Services;

use App\Jobs\SendNewEmail;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NewsServices
{
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

    public function store($data)
    {
        if (($data['status'] ?? null) === 'public' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (!empty($data['expires_at'])) {
            $data['expires_at'] = Carbon::parse($data['expires_at'])->format('Y-m-d H:i:s');
        }

        $emails = $data['emails'] ?? [];
        unset($data['emails']);

        return DB::transaction(function () use ($data, $emails) {
            $news = News::create($data);

            foreach ($emails as $email) {
                SendNewEmail::dispatch($email, $news->title, $news->content);
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

    public function update($id, array $data)
    {
        $news = News::findOrFail($id);

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
}