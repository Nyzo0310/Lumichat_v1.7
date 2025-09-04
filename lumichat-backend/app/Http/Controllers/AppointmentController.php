<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /** minutes per slot */
    private const STEP_MINUTES = 30;

    /** statuses that block a time from being offered again */
    private const BLOCKING_STATUSES = ['pending', 'confirmed', 'completed'];

    /** Mon–Fri only */
    private const WEEKDAY_MIN = 1; // Monday
    private const WEEKDAY_MAX = 5; // Friday

    /* Booking page */
    public function index()
    {
        $counselors = DB::table('tbl_counselors')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','name']);

        return view('appointment.index', compact('counselors'));
    }

    /* Slots (AJAX) */
    public function slots($counselorId, Request $request)
    {
        $dateStr = (string) $request->query('date', '');
        if (!$dateStr || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return response()->json(['slots'=>[], 'reason'=>'bad_request', 'message'=>'Provide date=YYYY-MM-DD.'], 400);
        }

        $counselor = DB::table('tbl_counselors')
            ->where('id', $counselorId)->where('is_active', true)->first();

        if (!$counselor) {
            return response()->json(['slots'=>[], 'reason'=>'not_found','message'=>'Counselor not found or inactive.'], 404);
        }

        $date  = Carbon::parse($dateStr)->startOfDay();
        $today = Carbon::now();
        $dow   = $date->dayOfWeek; // 0..6

        if ($dow < self::WEEKDAY_MIN || $dow > self::WEEKDAY_MAX) {
            return response()->json(['slots'=>[], 'reason'=>'weekend', 'message'=>'Counselors are available Monday to Friday only.']);
        }

        // One appointment per day (per student)
        $studentId = Auth::id();
        if ($studentId) {
            $hasSameDay = DB::table('tbl_appointments')
                ->where('student_id', $studentId)
                ->whereDate('scheduled_at', $date->toDateString())
                ->whereIn('status', self::BLOCKING_STATUSES)
                ->exists();

            if ($hasSameDay) {
                return response()->json(['slots'=>[], 'reason'=>'limit_reached', 'message'=>'You already have an appointment on this date.']);
            }
        }

        // Availability ranges
        $ranges = DB::table('tbl_counselor_availabilities')
            ->where('counselor_id', $counselorId)->where('weekday', $dow)
            ->orderBy('start_time')->get(['start_time','end_time']);

        if ($ranges->isEmpty()) {
            return response()->json(['slots'=>[], 'reason'=>'no_availability','message'=>'No counselor availability on that day.']);
        }

        // Already booked times for counselor
        $bookedTimes = DB::table('tbl_appointments')
            ->where('counselor_id', $counselorId)
            ->whereDate('scheduled_at', $date->toDateString())
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->pluck(DB::raw("DATE_FORMAT(scheduled_at, '%H:%i')"))
            ->all();
        $booked = array_flip($bookedTimes);

        $slots = [];
        foreach ($ranges as $r) {
            $start  = Carbon::parse($date->toDateString().' '.$r->start_time);
            $end    = Carbon::parse($date->toDateString().' '.$r->end_time);
            $cursor = $start->copy();

            while ($cursor->lt($end)) {
                $next = $cursor->copy()->addMinutes(self::STEP_MINUTES);
                if ($next->gt($end)) break;

                if ($date->isSameDay($today) && $cursor->lte($today)) {
                    $cursor->addMinutes(self::STEP_MINUTES);
                    continue;
                }

                $value = $cursor->format('H:i');
                if (!isset($booked[$value])) {
                    $slots[] = ['value'=>$value, 'label'=>$cursor->format('g:i A')];
                }
                $cursor->addMinutes(self::STEP_MINUTES);
            }
        }

        if (empty($slots)) {
            return response()->json([
                'slots'   => [],
                'reason'  => $bookedTimes ? 'fully_booked' : 'no_slots',
                'message' => $bookedTimes ? 'All slots are booked for that date.' : 'No available slots within working hours.',
            ]);
        }

        usort($slots, fn($a,$b)=>strcmp($a['value'],$b['value']));
        return response()->json(['slots'=>$slots]);
    }

    /* Store booking */
    public function store(Request $request)
    {
        $request->validate([
            'counselor_id' => 'required|integer|exists:tbl_counselors,id',
            'date'         => 'required|date_format:Y-m-d',
            'time'         => 'required|regex:/^\d{2}:\d{2}$/',
            'consent'      => 'accepted',
        ], [], ['counselor_id'=>'counselor', 'date'=>'date', 'time'=>'time']);

        $studentId   = Auth::id();
        $counselorId = (int) $request->counselor_id;
        $scheduledAt = Carbon::parse($request->date.' '.$request->time);
        $dow         = $scheduledAt->dayOfWeek;

        if ($dow < self::WEEKDAY_MIN || $dow > self::WEEKDAY_MAX) {
            return back()->withErrors(['date'=>'Counselors are available Monday to Friday only.'])->withInput();
        }

        $hasSameDay = DB::table('tbl_appointments')
            ->where('student_id', $studentId)
            ->whereDate('scheduled_at', $scheduledAt->toDateString())
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->exists();
        if ($hasSameDay) {
            return back()->withErrors(['date'=>'You already have an appointment on this date.'])->withInput();
        }

        if (!$this->isSlotAvailable($counselorId, $scheduledAt)) {
            return back()->withErrors(['time'=>'Sorry, that time is no longer available.'])->withInput();
        }

        $duplicate = DB::table('tbl_appointments')
            ->where('student_id', $studentId)
            ->where('counselor_id', $counselorId)
            ->where('scheduled_at', $scheduledAt)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->exists();
        if ($duplicate) {
            return back()->withErrors(['time'=>'You already have a booking at that time.'])->withInput();
        }

        DB::table('tbl_appointments')->insert([
            'student_id'   => $studentId,
            'counselor_id' => $counselorId,
            'scheduled_at' => $scheduledAt,
            'status'       => 'pending',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()->route('appointment.history')->with('status','Appointment booked successfully!');
    }

        /* History list */
    public function history(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $period = (string) ($request->query('period', $request->query('preoid', 'all')));
        $q      = trim((string) $request->query('q', ''));

        $now = now();

        $query = DB::table('tbl_appointments as a')
            ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
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
            $query->where('c.name', 'like', "%{$q}%");
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

    /* Single view */
    public function show($id)
    {
        $userId = Auth::id();

        $appointment = DB::table('tbl_appointments as a')
            ->join('tbl_counselors as c', 'c.id', '=', 'a.counselor_id')
            ->select(
                'a.*',
                'c.name  as counselor_name',
                'c.email as counselor_email',
                'c.phone as counselor_phone'
            )
            ->where('a.id', $id)
            ->where('a.student_id', $userId) // only owner can view
            ->first();

        abort_unless($appointment, 404);

        return view('appointment.show', compact('appointment'));
    }

    /* Helpers */
    private function isSlotAvailable(int $counselorId, Carbon $scheduledAt): bool
    {
        $date = $scheduledAt->copy()->startOfDay();
        $dow  = $date->dayOfWeek;

        // Mon–Fri only
        if ($dow < self::WEEKDAY_MIN || $dow > self::WEEKDAY_MAX) return false;

        // Must fit in counselor availability
        $ranges = DB::table('tbl_counselor_availabilities')
            ->where('counselor_id', $counselorId)
            ->where('weekday', $dow)
            ->get(['start_time','end_time']);

        $fitsRange = false;
        foreach ($ranges as $r) {
            $start = Carbon::parse($date->toDateString().' '.$r->start_time);
            $end   = Carbon::parse($date->toDateString().' '.$r->end_time);
            $endOfSlot = $scheduledAt->copy()->addMinutes(self::STEP_MINUTES);

            if ($scheduledAt->gte($start) && $endOfSlot->lte($end)) {
                $fitsRange = true;
                break;
            }
        }
        if (!$fitsRange) return false;

        // Not past
        if ($scheduledAt->lte(now())) return false;

        // Not already taken
        $conflict = DB::table('tbl_appointments')
            ->where('counselor_id', $counselorId)
            ->where('scheduled_at', $scheduledAt)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->exists();

        return !$conflict;
    }

    /* Cancel (student) */
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
        $start = \Carbon\Carbon::parse($ap->scheduled_at);
        if ($start->lte($now)) {
            return back()->withErrors(['error' => 'This appointment has already started/passed and cannot be canceled.']);
        }

        DB::table('tbl_appointments')
            ->where('id', $ap->id)
            ->update([
                'status'     => 'canceled',
                'updated_at' => now(),
            ]);

        return redirect()->route('appointment.history')->with('status', 'Appointment canceled.');
    }
}
