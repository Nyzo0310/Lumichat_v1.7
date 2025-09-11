<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\Request;

class ChatbotSessionController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim($request->input('q', ''));
        $dateKey = $request->input('date', 'all'); // all|7d|30d|month

        $sessions = ChatSession::query()
            ->with(['user'])
            ->when($q, function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($sub) use ($like) {
                    $sub->where('id', 'like', $like)
                        ->orWhere('topic_summary', 'like', $like)
                        ->orWhereHas('user', function ($uq) use ($like) {
                            $uq->where('name', 'like', $like)
                               ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->when($dateKey !== 'all', function ($query) use ($dateKey) {
                if ($dateKey === '7d')      $query->where('created_at', '>=', now()->subDays(7));
                elseif ($dateKey === '30d') $query->where('created_at', '>=', now()->subDays(30));
                elseif ($dateKey === 'month') $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.chatbot_sessions.index', compact('sessions', 'q', 'dateKey'));
    }

    public function show(ChatSession $session)
    {
        $session->load(['user', 'chats' => function ($q) {
            $q->orderBy('created_at'); // oldest → newest
        }]);

        return view('admin.chatbot_sessions.show', compact('session'));
    }
public function calendarCounts(ChatSession $session, Request $request)
{
    $from = $request->query('from'); // 'YYYY-MM-DD'
    $to   = $request->query('to');   // 'YYYY-MM-DD'

    if (!$from || !$to) {
        return response()->json(['error' => 'from/to required'], 422);
    }

    // Date-only comparisons avoid timezone edge-cases
    $counts = ChatSession::query()
        ->where('user_id', $session->user_id)
        ->whereDate('created_at', '>=', $from)
        ->whereDate('created_at', '<=', $to)
        ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
        ->groupBy('d')
        ->pluck('c', 'd')           // ['2025-09-07' => 1, ...]
        ->map(fn($v) => (int) $v);  // cast to int for the UI

    return response()->json(['counts' => $counts]);
}
}

