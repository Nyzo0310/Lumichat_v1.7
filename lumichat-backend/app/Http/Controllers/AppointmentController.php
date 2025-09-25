<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /** Minutes per slot */
    private const STEP_MINUTES = 30;

    /** Statuses that block a time from being offered again */
    private const BLOCKING_STATUSES = ['pending', 'confirmed', 'completed'];

    /** Student “active” statuses that block new bookings */
    private const STUDENT_ACTIVE_STATUSES = ['pending'];

    /** Mon–Fri only (1=Mon ... 5=Fri with isoWeekday) */
    private const WEEKDAY_MIN = 1; // Monday
    private const WEEKDAY_MAX = 5; // Friday

    /* --------------------------- Booking page --------------------------- */
    public function index()
    {
        // If the student already has an active appointment, send to History with a blocking modal
        if ($uid = Auth::id()) {
            $hasActive = DB::table('tbl_appointments')
                ->where('student_id', $uid)
                ->whereIn('status', self::STUDENT_ACTIVE_STATUSES)
                ->exists();

            if ($hasActive) {
                return redirect()
                    ->route('appointment.history')
                    ->with('swal', [
                        'icon'               => 'warning',
                        'title'              => 'You already have a pending appointment',
                        'text'               => 'Complete or cancel it before booking another.',
                        'confirmButtonText'  => 'OK',
                        'allowOutsideClick'  => false,
                        'allowEscapeKey'     => false,
                    ]);
            }
        }

        // ✅ No counselor list here anymore (pooled availability)
        return view('appointment.index');
    }

    /* -------------- Optional landing: decide index vs history ----------- */
    public function entrypoint(Request $request)
    {
        $userId = Auth::id();

        $hasActive = DB::table('tbl_appointments')
            ->where('student_id', $userId)
            ->whereIn('status', self::STUDENT_ACTIVE_STATUSES)
            ->exists();

        if ($hasActive) {
            return redirect()
                ->route('appointment.history')
                ->with('swal', [
                    'icon'               => 'warning',
                    'title'              => 'You already have a pending appointment',
                    'text'               => 'Complete or cancel it before booking another.',
                    'confirmButtonText'  => 'OK',
                    'allowOutsideClick'  => false,
                    'allowEscapeKey'     => false,
                ]);
        }

        return $this->index();
    }

    /* -------------------------- Slots (AJAX) ---------------------------- */
    // GET /appointment/slots?date=YYYY-MM-DD
    // Returns pooled slots: [{ value:"HH:MM", label:"g:i A", available:int }]
    public function slots(Request $request)
    {
        $dateStr = (string) $request->query('date', '');
        if (!$dateStr || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return response()->json(['slots'=>[], 'reason'=>'bad_request', 'message'=>'Provide date=YYYY-MM-DD.'], 400);
        }

        $date  = Carbon::parse($dateStr)->startOfDay();
        $today = Carbon::now();
        $dow   = $date->isoWeekday(); // 1..7 (Mon..Sun)

        if ($dow < self::WEEKDAY_MIN || $dow > self::WEEKDAY_MAX) {
            return response()->json(['slots'=>[], 'reason'=>'weekend', 'message'=>'Appointments are available Monday to Friday only.']);
        }

        // If the student already has an ACTIVE appointment (pending), do not offer slots
        if ($studentId = Auth::id()) {
            $hasActiveAny = DB::table('tbl_appointments')
                ->where('student_id', $studentId)
                ->whereIn('status', self::STUDENT_ACTIVE_STATUSES)
                ->exists();

            if ($hasActiveAny) {
                return response()->json([
                    'slots'   => [],
                    'reason'  => 'active_appointment',
                    'message' => 'You already have a pending appointment. Complete or cancel it before booking another.',
                ]);
            }
        }

        // Active counselors
        $counselors = DB::table('tbl_counselors')
            ->where('is_active', 1)
            ->pluck('id')
            ->all();

        if (empty($counselors)) {
            return response()->json(['slots'=>[], 'reason'=>'no_counselor', 'message'=>'No counselors are currently available.']);
        }

        // Build availability per counselor
        $timeBuckets = []; // 'HH:MM' => [counselor_id, ...]
        foreach ($counselors as $cid) {
            // availability ranges for that weekday
            $ranges = DB::table('tbl_counselor_availabilities')
                ->where('counselor_id', $cid)
                ->where('weekday', $dow)
                ->orderBy('start_time')->get(['start_time','end_time']);

            if ($ranges->isEmpty()) continue;

            // Booked times for that counselor and date
            $bookedTimes = DB::table('tbl_appointments')
                ->where('counselor_id', $cid)
                ->whereDate('scheduled_at', $date->toDateString())
                ->whereIn('status', self::BLOCKING_STATUSES)
                ->pluck(DB::raw("DATE_FORMAT(scheduled_at, '%H:%i')"))
                ->all();
            $booked = array_flip($bookedTimes);

            foreach ($ranges as $r) {
                $start  = Carbon::parse($date->toDateString().' '.$r->start_time);
                $end    = Carbon::parse($date->toDateString().' '.$r->end_time);
                $cursor = $start->copy();

                while ($cursor->lt($end)) {
                    $next = $cursor->copy()->addMinutes(self::STEP_MINUTES);
                    if ($next->gt($end)) break;

                    // prevent past times on same day
                    if ($date->isSameDay($today) && $cursor->lte($today)) {
                        $cursor->addMinutes(self::STEP_MINUTES);
                        continue;
                    }

                    $value = $cursor->format('H:i');
                    if (!isset($booked[$value])) {
                        $timeBuckets[$value] = $timeBuckets[$value] ?? [];
                        $timeBuckets[$value][] = $cid; // counselor free at this time
                    }
                    $cursor->addMinutes(self::STEP_MINUTES);
                }
            }
        }

        if (empty($timeBuckets)) {
            return response()->json([
                'slots'   => [],
                'reason'  => 'no_slots',
                'message' => 'No available slots within working hours.',
            ]);
        }

        // Reduce to pooled list with capacity = counselors free at that time MINUS already-held anonymous reservations
        $slots = [];
        foreach ($timeBuckets as $hhmm => $freeCounselors) {
            $t = Carbon::parse($date->toDateString().' '.$hhmm);

            // count how many total appointments exist at this exact date&time with blocking statuses (regardless of counselor assignment)
            $takenAtTime = DB::table('tbl_appointments')
                ->whereDate('scheduled_at', $t->toDateString())
                ->whereTime('scheduled_at', $t->format('H:i:s'))
                ->whereIn('status', self::BLOCKING_STATUSES)
                ->count();

            $capacity = max(0, count($freeCounselors) - $takenAtTime);
            if ($capacity > 0) {
                $slots[] = [
                    'value'     => $hhmm,
                    'label'     => $t->format('g:i A'),
                    'available' => $capacity,
                ];
            }
        }

        usort($slots, fn($a,$b)=>strcmp($a['value'],$b['value']));

        return response()->json(['slots'=>$slots]);
    }

    /* --------------------------- Store booking -------------------------- */
    // Student submits date + time + consent; system DOES NOT assign counselor
    // We reserve capacity anonymously; admin assigns counselor later.
    public function store(Request $request)
    {
        $request->validate([
            'date'    => 'required|date_format:Y-m-d',
            'time'    => 'required|regex:/^\d{2}:\d{2}$/',
            'consent' => 'accepted',
        ], [], ['date'=>'date', 'time'=>'time']);

        $studentId   = Auth::id();
        $scheduledAt = Carbon::parse($request->date.' '.$request->time.':00');

        // One ACTIVE appointment at a time (pending)
        $hasActiveAny = DB::table('tbl_appointments')
            ->where('student_id', $studentId)
            ->whereIn('status', self::STUDENT_ACTIVE_STATUSES)
            ->exists();
        if ($hasActiveAny) {
            return back()->withErrors([
                'error' => 'You already have a pending appointment. Complete or cancel it before booking another.',
            ])->withInput();
        }

        // Mon–Fri only + not past
        $dow = $scheduledAt->isoWeekday();
        if ($dow < self::WEEKDAY_MIN || $dow > self::WEEKDAY_MAX) {
            return back()->withErrors(['date'=>'Appointments are available Monday to Friday only.'])->withInput();
        }
        if ($scheduledAt->lte(now())) {
            return back()->withErrors(['time'=>'Please choose a future time.'])->withInput();
        }

        // One appointment per day (any blocking status)
        $hasSameDay = DB::table('tbl_appointments')
            ->where('student_id', $studentId)
            ->whereDate('scheduled_at', $scheduledAt->toDateString())
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->exists();
        if ($hasSameDay) {
            return back()->withErrors(['date'=>'You already have an appointment on this date.'])->withInput();
        }

        // Determine how many counselors are actually free at that slot
        $freeCounselors = $this->counselorsFreeAt($scheduledAt);
        if (empty($freeCounselors)) {
            return back()->withErrors(['time'=>'Sorry, that time is no longer available.'])->withInput();
        }

        // Capacity control (race-safe using transaction + recheck)
        try {
            DB::transaction(function () use ($studentId, $scheduledAt, $freeCounselors) {
                // Re-count blockers at the exact second (00) to avoid races
                $takenAtTime = DB::table('tbl_appointments')
                    ->whereDate('scheduled_at', $scheduledAt->toDateString())
                    ->whereTime('scheduled_at', $scheduledAt->format('H:i:s'))
                    ->whereIn('status', self::BLOCKING_STATUSES)
                    ->lockForUpdate()
                    ->count();

                $capacity = count($freeCounselors) - $takenAtTime;
                if ($capacity <= 0) {
                    throw new \RuntimeException('FULL');
                }

                // Insert WITHOUT counselor_id; admin will assign later
                DB::table('tbl_appointments')->insert([
                    'student_id'   => $studentId,
                    'counselor_id' => null,
                    'scheduled_at' => $scheduledAt,
                    'status'       => 'pending',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
        \Log::error('APPT INSERT FAILED', [
            'code' => $e->errorInfo[1] ?? null,
            'sqlstate' => $e->errorInfo[0] ?? null,
            'message' => $e->getMessage(),
        ]);

        if ((int)($e->errorInfo[1] ?? 0) !== 1062) {
            return back()->withErrors([
                'error' => 'Unable to book the appointment right now. (ERR#'.((int)($e->errorInfo[1] ?? 0)).')'
            ])->withInput();
        }
    }

        // Success → neutral message (no counselor shown)
        return redirect()
            ->route('appointment.history')
            ->with('swal', [
                'icon'  => 'success',
                'title' => 'Appointment booked!',
                'html'  => sprintf(
                    '<div style="text-align:left">
                       <div><b>Date:</b> %s</div>
                       <div><b>Time:</b> %s</div>
                       <div style="margin-top:.25rem;color:#475569"><em>A counselor has not been assigned yet. You’ll be notified once an admin assigns one.</em></div>
                     </div>',
                    e($scheduledAt->format('M d, Y')),
                    e($scheduledAt->format('g:i A'))
                ),
                'confirmButtonText' => 'OK',
            ]);
    }

    /* ----------------------------- History ----------------------------- */
    public function history(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $period = (string) ($request->query('period', $request->query('preoid', 'all')));
        $q      = trim((string) $request->query('q', ''));

        $now = now();

        $query = DB::table('tbl_appointments as a')
            ->leftJoin('tbl_counselors as c', 'c.id', '=', 'a.counselor_id') // <-- leftJoin to allow null counselor
            ->select([
                'a.id','a.student_id','a.counselor_id','a.scheduled_at','a.status',
                'c.name as counselor_name','c.email as counselor_email','c.phone as counselor_phone',
                'a.final_note','a.finalized_at',
            ])
            ->where('a.student_id', Auth::id());

        if ($status !== 'all') $query->where('a.status', $status);

        switch ($period) {
            case 'today':
                $query->whereDate('a.scheduled_at', $now->toDateString());
                break;
            case 'upcoming':
                $query->where('a.scheduled_at', '>=', $now);
                break;
            case 'this_week':
                $query->whereBetween('a.scheduled_at', [
                    $now->copy()->startOfWeek(), $now->copy()->endOfWeek()
                ]);
                break;
            case 'this_month':
                $query->whereBetween('a.scheduled_at', [
                    $now->copy()->startOfMonth(), $now->copy()->endOfMonth()
                ]);
                break;
            case 'past':
                $query->where('a.scheduled_at', '<', $now);
                break;
            case 'all':
            default:
                // no date filter
                break;
        }

        if ($q !== '') {
            $query->where(function($w) use ($q) {
                $w->where('c.name', 'like', "%{$q}%")
                  ->orWhereNull('c.id'); // include “awaiting assignment” in searches
            });
        }

        $appointments = $query
            ->orderByDesc('a.scheduled_at')
            ->paginate(10)
            ->withQueryString();

        return view('appointment.history', [
            'appointments' => $appointments,
            'status'       => $status,
            'period'       => $period,
            'q'            => $q,
        ]);
    }

    public function show($id)
    {
        $userId = Auth::id();

        $appointment = DB::table('tbl_appointments as a')
            ->leftJoin('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
            ->select(
                'a.*',
                'c.name  as counselor_name',
                'c.email as counselor_email',
                'c.phone as counselor_phone'
            )
            ->where('a.id', $id)
            ->where('a.student_id', $userId)
            ->first();

        abort_unless($appointment, 404);

        return view('appointment.show', compact('appointment'));
    }

    /* ------------------------------ Helpers ---------------------------- */

    /** Return counselor IDs who are free at the exact $scheduledAt slot. */
    private function counselorsFreeAt(Carbon $scheduledAt): array
    {
        $date = $scheduledAt->copy()->startOfDay();
        $dow  = $date->isoWeekday();

        $active = DB::table('tbl_counselors')
            ->where('is_active', 1)
            ->pluck('id')->all();
        if (empty($active)) return [];

        $endOfSlot = $scheduledAt->copy()->addMinutes(self::STEP_MINUTES);

        $free = [];
        foreach ($active as $cid) {
            // availability that fits this slot
            $ranges = DB::table('tbl_counselor_availabilities')
                ->where('counselor_id', $cid)
                ->where('weekday', $dow)
                ->get(['start_time','end_time']);

            $fits = false;
            foreach ($ranges as $r) {
                $start = Carbon::parse($date->toDateString().' '.$r->start_time);
                $end   = Carbon::parse($date->toDateString().' '.$r->end_time);
                if ($scheduledAt->gte($start) && $endOfSlot->lte($end)) { $fits = true; break; }
            }
            if (!$fits) continue;

            // not booked at that exact time
            $taken = DB::table('tbl_appointments')
                ->where('counselor_id', $cid)
                ->where('scheduled_at', $scheduledAt)
                ->whereIn('status', self::BLOCKING_STATUSES)
                ->exists();

            if (!$taken) $free[] = $cid;
        }
        return $free;
    }

    /* --------------------------- Cancel (student) ---------------------- */
    public function cancel($id, Request $request)
    {
        $userId = Auth::id();

        $ap = DB::table('tbl_appointments')
            ->where('id', $id)
            ->where('student_id', $userId)
            ->first();

        if (!$ap) {
            return back()->withErrors(['error' => 'Appointment not found.']);
        }

        // Only pending + future can be canceled
        if ($ap->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending appointments can be canceled.']);
        }

        $now   = now();
        $start = Carbon::parse($ap->scheduled_at);
        if ($start->lte($now)) {
            return back()->withErrors(['error' => 'This appointment has already started/passed and cannot be canceled.']);
        }

        DB::table('tbl_appointments')
            ->where('id', $ap->id)
            ->update([
                'status'     => 'canceled',
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('appointment.history')
            ->with('swal', [
                'icon'              => 'success',
                'title'             => 'Appointment canceled',
                'text'              => 'Your appointment has been canceled successfully.',
                'confirmButtonText' => 'OK',
            ]);
    }
}
