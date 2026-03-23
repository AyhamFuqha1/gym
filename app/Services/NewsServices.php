<?php

namespace App\Services;

use App\Models\News;
use Carbon\Carbon;

class NewsServices
{


    public function getPaginatedNews($perPage = 10)
    {
        $paginated = News::with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $paginated->getCollection()->transform(function (News $news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'content' => $news->content,
                'status' => $news->status,
                'author_name' => optional($news->user)->name,
                'formatted_date' => optional($news->created_at)->format('M j, Y'),
            ];
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
        return News::create($data);
    }

    public function destroy($id)
    {
        $new = News::findOrFail($id);
        return $new->update(['status'=>'deleted']);
    }

    public function update($id, array $data)
    {
        $news = News::findOrFail($id);
        $news->update($data);
        return $news;
    }
}
