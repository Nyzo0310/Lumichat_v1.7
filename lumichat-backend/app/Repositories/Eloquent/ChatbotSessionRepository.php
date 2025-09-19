<?php

namespace App\Repositories\Eloquent;

use App\Models\ChatSession;
use App\Repositories\Contracts\ChatbotSessionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ChatbotSessionRepository implements ChatbotSessionRepositoryInterface
{
    /** -------- CRUD (standardized) -------- */

    public function all(): Collection
    {
        return ChatSession::orderByDesc('created_at')->get();
    }

    public function findById(int $id, array $with = []): ?object
    {
        return ChatSession::with($with)->find($id);
    }

    public function create(array $data): object
    {
        return ChatSession::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $session = ChatSession::findOrFail($id);
        return (bool) $session->update($data);
    }

    public function delete(int $id): bool
    {
        $session = ChatSession::findOrFail($id);
        return (bool) $session->delete();
    }

    /** -------- Admin index: search + date filters + pagination -------- */

    public function paginateWithFilters(string $q = '', string $dateKey = 'all', int $perPage = 10): LengthAwarePaginator
    {
        $query = ChatSession::query()->with('user');

        // free text search across id, topic_summary, user name/email
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function (Builder $sub) use ($like) {
                $sub->where('id', 'like', $like)
                    ->orWhere('topic_summary', 'like', $like)
                    ->orWhereHas('user', function (Builder $uq) use ($like) {
                        $uq->where('name', 'like', $like)
                           ->orWhere('email', 'like', $like);
                    });
            });
        }

        // relative date filters
        $this->applyDateKeyFilter($query, $dateKey);

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** -------- Admin show: one session + ordered chats -------- */

    public function findWithOrderedChats(int $id): ?object
    {
        return ChatSession::with([
                'user',
                'chats' => fn ($q) => $q->orderBy('created_at'), // oldest → newest
            ])->find($id);
    }

    /** -------- Calendar heatmap: per-day counts for a user -------- */

    public function perDayCountsForUser(int $userId, string $fromDate, string $toDate): array
    {
        return ChatSession::query()
            ->where('user_id', $userId)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    public function getUserIdBySessionId(int $sessionId): ?int
    {
        return ChatSession::query()->where('id', $sessionId)->value('user_id');
    }

    /** -------- Private: date-key filter logic (mirrors your controller) -------- */

    private function applyDateKeyFilter(Builder $query, string $dateKey): Builder
    {
        if ($dateKey === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($dateKey === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($dateKey === 'month') {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }
        return $query;
    }
}
