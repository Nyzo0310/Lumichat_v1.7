<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotSessionController extends Controller
{
    // ==== Constants (dedupe magic strings / numbers) ====
    private const FLASH_SWAL       = 'swal';
    private const PER_PAGE         = 10;
    private const DATE_KEY_ALL     = 'all';
    private const DATE_KEYS        = ['all', '7d', '30d', 'month'];

    /**
     * List chatbot sessions with optional free-text and date filters.
     */
    public function index(Request $request): View
    {
        $q       = \trim((string) $request->input('q', ''));
        $dateKey = \in_array($request->input('date', self::DATE_KEY_ALL), self::DATE_KEYS, true)
            ? $request->input('date', self::DATE_KEY_ALL)
            : self::DATE_KEY_ALL;

        $sessions = ChatSession::query()
            ->with(['user'])
            ->when($q !== '', function ($query) use ($q) {
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
            ->when($dateKey !== self::DATE_KEY_ALL, fn ($query) => $this->applyDateKeyFilter($query, $dateKey))
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.chatbot_sessions.index', compact('sessions', 'q', 'dateKey'));
    }

    /**
     * Show a single session with ordered chats.
     */
    public function show(ChatSession $session): View
    {
        $session->load([
            'user',
            'chats' => function ($q) {
                $q->orderBy('created_at'); // oldest → newest
            },
        ]);

        return view('admin.chatbot_sessions.show', compact('session'));
    }

    /**
     * Return per-day counts for a user's sessions (within a date range).
     */
    public function calendarCounts(ChatSession $session, Request $request): JsonResponse
    {
        $from = $request->query('from'); // 'YYYY-MM-DD'
        $to   = $request->query('to');   // 'YYYY-MM-DD'

        // Fail fast on missing params (same semantics as your current behavior)
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
            ->map(fn ($v) => (int) $v); // cast to int for the UI

        return response()->json(['counts' => $counts]);
    }

    // ==== Private helpers (no logic change; just isolate branching) ====

    /**
     * Apply the relative date filter for the index() listing.
     * Keeps your original branches exactly the same.
     */
    private function applyDateKeyFilter($query, string $dateKey)
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
