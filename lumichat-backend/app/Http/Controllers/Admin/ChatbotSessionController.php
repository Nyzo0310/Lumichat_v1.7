<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ChatbotSessionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotSessionController extends Controller
{
    // constants preserved
    private const FLASH_SWAL   = 'swal';
    private const PER_PAGE     = 10;
    private const DATE_KEY_ALL = 'all';
    private const DATE_KEYS    = ['all', '7d', '30d', 'month'];

    public function __construct(
        protected ChatbotSessionRepositoryInterface $sessions
    ) {}

    /** List chatbot sessions with optional free-text and date filters. */
    public function index(Request $request): View
    {
        $q       = trim((string) $request->input('q', ''));
        $dateReq = (string) $request->input('date', self::DATE_KEY_ALL);
        $dateKey = in_array($dateReq, self::DATE_KEYS, true) ? $dateReq : self::DATE_KEY_ALL;

        $sessions = $this->sessions->paginateWithFilters($q, $dateKey, self::PER_PAGE);

        return view('admin.chatbot_sessions.index', compact('sessions', 'q', 'dateKey'));
    }

    /** Show a single session with ordered chats. */
    public function show(int $id): View
    {
        $session = $this->sessions->findWithOrderedChats($id);
        abort_unless($session, 404);

        return view('admin.chatbot_sessions.show', compact('session'));
    }

    /** Return per-day counts for a user's sessions (within a date range). */
    public function calendarCounts(int $id, Request $request): JsonResponse
    {
        $from = $request->query('from'); // 'YYYY-MM-DD'
        $to   = $request->query('to');   // 'YYYY-MM-DD'

        if (!$from || !$to) {
            return response()->json(['error' => 'from/to required'], 422);
        }

        $userId = $this->sessions->getUserIdBySessionId($id);
        if (!$userId) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $counts = $this->sessions->perDayCountsForUser((int) $userId, $from, $to);

        return response()->json(['counts' => $counts]);
    }
public function exportPdf(Request $request)
{
    $q       = trim((string) $request->input('q', ''));
    $dateReq = (string) $request->input('date', self::DATE_KEY_ALL);
    $dateKey = in_array($dateReq, self::DATE_KEYS, true) ? $dateReq : self::DATE_KEY_ALL;

    $rows = method_exists($this->sessions, 'allWithFilters')
        ? $this->sessions->allWithFilters($q, $dateKey)
        : (function () use ($q, $dateKey) {
              $p = $this->sessions->paginateWithFilters($q, $dateKey, PHP_INT_MAX);
              return method_exists($p, 'items') ? collect($p->items()) : collect($p);
          })();

    $pdf = app('dompdf.wrapper');
$pdf->setPaper('a4', 'portrait');
$pdf->loadView('admin.chatbot_sessions.pdf', [
    'rows'        => $rows,
    'q'           => $q,
    'dateKey'     => $dateKey,
    'generatedAt' => now()->format('Y-m-d H:i'),
]);
return $pdf->download('Chatbot_Sessions_'.now()->format('Ymd_His').'.pdf');
}   
}

