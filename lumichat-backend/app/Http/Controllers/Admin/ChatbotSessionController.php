<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ChatbotSessionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatbotSessionController extends Controller
{
    private const FLASH_SWAL   = 'swal';
    private const PER_PAGE     = 10;
    private const DATE_KEY_ALL = 'all';
    private const DATE_KEYS    = ['all', '7d', '30d', 'month'];

    /** Minutes per slot (keep in sync with student side) */
    private const STEP_MINUTES = 30;

    /** Appointments that block a counselor’s slot */
    private const BLOCKING_STATUSES = ['pending','confirmed','completed'];

    /** For THIS session, these statuses mean “already booked” (disable Book button) */
    private const SESSION_ACTIVE_STATUSES = ['pending','confirmed'];

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

        // Session IDs that already have any appointment (pending/confirmed/completed) -> handled
        $handledSessionIds = DB::table('tbl_appointments')
            ->whereNotNull('chatbot_session_id')
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->pluck('chatbot_session_id')
            ->unique()
            ->all();

        return view('admin.chatbot_sessions.index', compact('sessions', 'q', 'dateKey', 'handledSessionIds'));
    }

    /** Show a single session with ordered chats. */
    public function show(int $id): View
    {
        $session = $this->sessions->findWithOrderedChats($id);
        abort_unless($session, 404);

        $hasActiveForThisSession = DB::table('tbl_appointments')
            ->where('chatbot_session_id', $session->id)
            ->whereIn('status', self::SESSION_ACTIVE_STATUSES)
            ->exists();

        return view('admin.chatbot_sessions.show', compact('session','hasActiveForThisSession'));
    }

    /** Return per-day counts for a user's sessions (within a date range). */
    public function calendarCounts(int $id, Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to   = $request->query('to');

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

    /** Get counselor-wise slots for a date (Mon–Fri). */
    public function slots(int $id, Request $request): JsonResponse
    {
        $dateStr = (string) $request->query('date', '');
        if (!$dateStr || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return response()->json(['message' => 'Provide date=YYYY-MM-DD.'], 422);
        }

        $date = Carbon::parse($dateStr)->startOfDay();
        $dow  = $date->dayOfWeek; // 0..6 (Sun..Sat)

        if ($dow < 1 || $dow > 5) {
            return response()->json([
                'counselors' => [],
                'slots'      => [],
                'message'    => 'Appointments are available Monday to Friday only.'
            ]);
        }

        // Active counselors
        $counselors = DB::table('tbl_counselors')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id','name']);

        if ($counselors->isEmpty()) {
            return response()->json(['counselors'=>[], 'slots'=>[], 'message'=>'No active counselors.']);
        }

        // Build per-counselor 30-min slots based on weekly availability and remove taken
        $slotsByCounselor = [];
        foreach ($counselors as $c) {
            $ranges = DB::table('tbl_counselor_availabilities')
                ->where('counselor_id', $c->id)
                ->where('weekday', $dow)
                ->orderBy('start_time')
                ->get(['start_time','end_time']);

            $col = [];
            foreach ($ranges as $r) {
                $start  = Carbon::parse($date->toDateString().' '.$r->start_time);
                $end    = Carbon::parse($date->toDateString().' '.$r->end_time);
                $cursor = $start->copy();

                while ($cursor->lt($end)) {
                    $next = $cursor->copy()->addMinutes(self::STEP_MINUTES);
                    if ($next->gt($end)) break;

                    // skip past times if same-day
                    if ($date->isToday() && $cursor->lte(now())) {
                        $cursor->addMinutes(self::STEP_MINUTES);
                        continue;
                    }

                    // taken?
                    $taken = DB::table('tbl_appointments')
                        ->where('counselor_id', $c->id)
                        ->where('scheduled_at', $cursor)
                        ->whereIn('status', self::BLOCKING_STATUSES)
                        ->exists();

                    if (!$taken) {
                        $col[] = [
                            'value' => $cursor->format('H:i'),
                            'label' => $cursor->format('g:i A'),
                        ];
                    }
                    $cursor->addMinutes(self::STEP_MINUTES);
                }
            }
            $col = collect($col)->unique('value')->sortBy('value')->values()->all();
            $slotsByCounselor[$c->id] = $col;
        }

        return response()->json([
            'counselors' => $counselors->map(fn($r)=>['id'=>$r->id,'name'=>$r->name])->values(),
            'slots'      => $slotsByCounselor,
        ]);
    }

    /** Admin books appointment for the session’s student with a chosen counselor+time. */
    public function book(int $id, Request $request): JsonResponse
    {
        // Which student?
        $session = $this->sessions->findWithOrderedChats($id);
        if (!$session || empty($session->user_id)) {
            return response()->json(['message'=>'Session not found.'], 404);
        }
        $studentId = (int) $session->user_id;

        // Guard: THIS session already has active appt (pending/confirmed)?
        $hasActiveForThisSession = DB::table('tbl_appointments')
            ->where('chatbot_session_id', $session->id)
            ->whereIn('status', self::SESSION_ACTIVE_STATUSES)
            ->exists();
        if ($hasActiveForThisSession) {
            return response()->json([
                'message' => 'This session already has an active appointment (pending/confirmed).'
            ], 409);
        }

        $validated = $request->validate([
            'date'         => ['required','date_format:Y-m-d'],
            'time'         => ['required','regex:/^\d{2}:\d{2}$/'],
            'counselor_id' => ['required','integer','exists:tbl_counselors,id'],
        ]);

        $dt  = Carbon::parse($validated['date'].' '.$validated['time'].':00');
        $dow = $dt->dayOfWeek; // 0..6
        if ($dow < 1 || $dow > 5) {
            return response()->json(['message'=>'Appointments are available Monday to Friday only.'], 422);
        }
        if ($dt->lte(now())) {
            return response()->json(['message'=>'Please choose a future time.'], 422);
        }

        $counselorId = (int) $validated['counselor_id'];

        // Verify the counselor is actually available for that weekday and slot boundary
        $endOfSlot = $dt->copy()->addMinutes(self::STEP_MINUTES);
        $fits = DB::table('tbl_counselor_availabilities')
            ->where('counselor_id', $counselorId)
            ->where('weekday', $dow)
            ->get(['start_time','end_time'])
            ->contains(function($r) use ($dt, $endOfSlot) {
                $start = Carbon::parse($dt->toDateString().' '.$r->start_time);
                $end   = Carbon::parse($dt->toDateString().' '.$r->end_time);
                return $dt->gte($start) && $endOfSlot->lte($end);
            });
        if (!$fits) {
            return response()->json(['message'=>'Selected time is outside counselor availability.'], 422);
        }

        try {
            DB::transaction(function () use ($studentId, $counselorId, $dt, $session) {
                // Re-check in TX (race-safe)
                $activeNow = DB::table('tbl_appointments')
                    ->where('chatbot_session_id', $session->id)
                    ->whereIn('status', self::SESSION_ACTIVE_STATUSES)
                    ->lockForUpdate()
                    ->exists();
                if ($activeNow) {
                    throw new \RuntimeException('SESSION_ACTIVE');
                }

                // Counselor slot free?
                $taken = DB::table('tbl_appointments')
                    ->where('counselor_id', $counselorId)
                    ->where('scheduled_at', $dt)
                    ->whereIn('status', self::BLOCKING_STATUSES)
                    ->lockForUpdate()
                    ->exists();
                if ($taken) {
                    throw new \RuntimeException('TAKEN');
                }

                DB::table('tbl_appointments')->insert([
                    'student_id'         => $studentId,
                    'counselor_id'       => $counselorId,
                    'scheduled_at'       => $dt,
                    'status'             => 'confirmed',   // admin assigns directly
                    'chatbot_session_id' => $session->id,  // link to THIS session
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'TAKEN') {
                return response()->json(['message'=>'That counselor/time just filled. Pick another slot.'], 409);
            }
            if ($e->getMessage() === 'SESSION_ACTIVE') {
                return response()->json(['message'=>'This session already has an active appointment (pending/confirmed).'], 409);
            }
            throw $e;
        }

        return response()->json([
            'ok'   => true,
            'html' => sprintf(
                '<div style="text-align:left">
                   <div><b>Student:</b> %s</div>
                   <div><b>Counselor:</b> %s</div>
                   <div><b>Date:</b> %s</div>
                   <div><b>Time:</b> %s</div>
                 </div>',
                e($session->user->name ?? ('#'.$studentId)),
                e(DB::table('tbl_counselors')->where('id',$counselorId)->value('name') ?? '—'),
                e($dt->format('M d, Y')),
                e($dt->format('g:i A'))
            )
        ]);
    }
}
