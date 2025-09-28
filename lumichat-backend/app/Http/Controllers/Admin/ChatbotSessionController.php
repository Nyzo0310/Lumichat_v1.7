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
    // inside ChatbotSessionController.php
   public function slots(int $id, Request $request): JsonResponse
    {
        $dateStr = (string) $request->query('date', '');
        if (!$dateStr || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return response()->json(['message' => 'Provide date=YYYY-MM-DD.'], 422);
        }

        $date   = Carbon::parse($dateStr)->startOfDay();
        $now    = now();
        $dowIso = $date->isoWeekday(); // 1..7 (Mon..Sun)

        if ($dowIso < 1 || $dowIso > 5) {
            return response()->json([
                'counselors' => [],
                'slots'      => [],
                'pooled'     => [],
                'message'    => 'Appointments are available Monday to Friday only.'
            ]);
        }

        $counselors = DB::table('tbl_counselors')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id','name']);

        if ($counselors->isEmpty()) {
            return response()->json(['counselors'=>[], 'slots'=>[], 'pooled'=>[], 'message'=>'No active counselors.']);
        }

        // helper to snap to 30-min grid
        $snap = function (Carbon $dt): Carbon {
            $m = (int) floor($dt->minute / 30) * 30;
            return $dt->copy()->setTime($dt->hour, $m, 0);
        };

        $slotsByCounselor = [];
        $allTimes = []; // collect all unique HH:MM we will later count pooled capacity for

        foreach ($counselors as $c) {
            $ranges = DB::table('tbl_counselor_availabilities')
                ->where('counselor_id', $c->id)
                ->where('weekday', $dowIso)
                ->orderBy('start_time')
                ->get(['start_time','end_time']);

            $col = [];
            foreach ($ranges as $r) {
                if (!is_string($r->start_time) || !is_string($r->end_time) || $r->start_time==='' || $r->end_time==='') {
                    continue;
                }
                $cursor = $snap(Carbon::parse($date->toDateString().' '.$r->start_time)->second(0));
                $end    = Carbon::parse($date->toDateString().' '.$r->end_time)->second(0);

                while ($cursor->lt($end)) {
                    $slot = $snap($cursor);
                    $next = $slot->copy()->addMinutes(30);
                    if ($next->gt($end)) break;

                    $isPast = $date->isSameDay($now) && $slot->lte($now);

                    // block only if this counselor already taken at this exact time
                    $taken = DB::table('tbl_appointments')
                        ->where('counselor_id', $c->id)
                        ->where('scheduled_at', $slot)
                        ->whereIn('status', self::BLOCKING_STATUSES)
                        ->exists();

                    if (!$taken) {
                        $hhmm = $slot->format('H:i');
                        $col[] = [
                            'value'    => $hhmm,
                            'label'    => $slot->format('g:i A'),
                            'disabled' => $isPast,
                        ];
                        $allTimes[$hhmm] = true; // remember for pooled counting
                    }

                    $cursor = $cursor->addMinutes(30);
                }
            }

            $slotsByCounselor[$c->id] = collect($col)->unique('value')->sortBy('value')->values()->all();
        }

        // 🔢 Pooled capacity per HH:MM (how many counselors are free)
        $repo = app(\App\Repositories\Contracts\AppointmentRepositoryInterface::class);
        $pooled = [];
        foreach (array_keys($allTimes) as $hhmm) {
            $t = Carbon::parse($date->toDateString().' '.$hhmm.':00');
            $pooled[$hhmm] = count($repo->counselorIdsFreeAt($t));
        }

        return response()->json([
            'counselors' => $counselors->map(fn($r)=>['id'=>$r->id,'name'=>$r->name])->values(),
            'slots'      => $slotsByCounselor,
            'pooled'     => $pooled, // <-- NEW
        ]);
    }


    /** Admin books appointment for the session’s student with a chosen counselor+time. */
    public function book(int $id, Request $request): JsonResponse
    {
        $session = $this->sessions->findWithOrderedChats($id);
        if (!$session || empty($session->user_id)) {
            return response()->json(['message'=>'Session not found.'], 404);
        }
        $studentId = (int) $session->user_id;

        $hasActiveForThisSession = DB::table('tbl_appointments')
            ->where('chatbot_session_id', $session->id)
            ->whereIn('status', self::SESSION_ACTIVE_STATUSES)
            ->exists();
        if ($hasActiveForThisSession) {
            return response()->json(['message' => 'This session already has an active appointment (pending/confirmed).'], 409);
        }

        $validated = $request->validate([
            'date'         => ['required','date_format:Y-m-d'],
            'time'         => ['required','regex:/^\d{2}:\d{2}$/'],
            'counselor_id' => ['required','integer','exists:tbl_counselors,id'],
        ]);

        // snap to grid
        $raw = Carbon::parse($validated['date'].' '.$validated['time'].':00')->second(0);
        $slot = (function(Carbon $dt){ $m=(int)floor($dt->minute/30)*30; return $dt->copy()->setTime($dt->hour,$m,0);} )($raw);
        if ($raw->ne($slot)) {
            return response()->json(['message'=>'Please choose a 30-minute step time (e.g., 09:00, 09:30).'], 422);
        }

        $dowIso = $slot->isoWeekday();
        if ($dowIso < 1 || $dowIso > 5) {
            return response()->json(['message'=>'Appointments are available Monday to Friday only.'], 422);
        }
        if ($slot->lte(now())) {
            return response()->json(['message'=>'Please choose a future time.'], 422);
        }

        $counselorId = (int) $validated['counselor_id'];

        // verify counselor weekly availability
        $fits = DB::table('tbl_counselor_availabilities')
            ->where('counselor_id', $counselorId)
            ->where('weekday', $dowIso)
            ->get(['start_time','end_time'])
            ->contains(function($r) use ($slot) {
                $endOf = $slot->copy()->addMinutes(30);
                $start = Carbon::parse($slot->toDateString().' '.$r->start_time);
                $end   = Carbon::parse($slot->toDateString().' '.$r->end_time);
                return $slot->gte($start) && $endOf->lte($end);
            });
        if (!$fits) {
            return response()->json(['message'=>'Selected time is outside counselor availability.'], 422);
        }

        try {
            DB::transaction(function () use ($studentId, $counselorId, $slot, $session) {
                // re-check active for this session (race)
                $activeNow = DB::table('tbl_appointments')
                    ->where('chatbot_session_id', $session->id)
                    ->whereIn('status', self::SESSION_ACTIVE_STATUSES)
                    ->lockForUpdate()
                    ->exists();
                if ($activeNow) throw new \RuntimeException('SESSION_ACTIVE');

                // counselor not taken at that exact slot?
                $taken = DB::table('tbl_appointments')
                    ->where('counselor_id', $counselorId)
                    ->where('scheduled_at', $slot)
                    ->whereIn('status', self::BLOCKING_STATUSES)
                    ->lockForUpdate()
                    ->exists();
                if ($taken) throw new \RuntimeException('TAKEN');

                DB::table('tbl_appointments')->insert([
                    'student_id'         => $studentId,
                    'counselor_id'       => $counselorId,
                    'scheduled_at'       => $slot,
                    'status'             => 'confirmed',
                    'chatbot_session_id' => $session->id,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'TAKEN')          return response()->json(['message'=>'That counselor/time just filled. Pick another slot.'], 409);
            if ($e->getMessage() === 'SESSION_ACTIVE') return response()->json(['message'=>'This session already has an active appointment (pending/confirmed).'], 409);
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
                e($slot->format('M d, Y')),
                e($slot->format('g:i A'))
            )
        ]);
    }
}
