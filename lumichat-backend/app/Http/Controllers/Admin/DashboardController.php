<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // ==== Constants (dedupe magic strings / lists) ====
    private const VIEW_DASHBOARD = 'admin.dashboard';
    private const APPT_TABLE_CANDIDATES = ['tbl_appointment', 'appointments', 'tbl_appointments'];
    private const APPT_WEEK_COL_CANDIDATES = ['created_at', 'scheduled_at', 'appointment_at', 'datetime'];
    private const APPT_DATE_FALLBACK_COL = 'date';
    private const APPT_TIME_FALLBACK_COL = 'time';

    /**
     * Render dashboard (Blade).
     */
    public function index(): View
    {
        $data = $this->buildStatsPayload();

        // Cast arrays -> objects for Blade & parse times
        $recentAppointments = collect($data['recentAppointments'])->map(function ($r) {
            $o = (object) $r;
            $o->when = !empty($o->when) ? Carbon::parse($o->when) : null;
            return $o;
        });

        $activities = collect($data['activities'])->map(function ($r) {
            $o = \is_array($r) ? (object) $r : $r;
            $o->created_at = !empty($o->created_at) ? Carbon::parse($o->created_at) : null;
            return $o;
        });

        $recentChatSessions = collect($data['recentChatSessions'])->map(function ($r) {
            $o = (object) $r;
            $o->created_at = !empty($o->created_at) ? Carbon::parse($o->created_at) : null;
            return $o;
        });

        return view(self::VIEW_DASHBOARD, [
            // KPI numbers
            'appointmentsTotal'     => $data['kpis']['appointmentsTotal'],
            'criticalCasesTotal'    => $data['kpis']['criticalCasesTotal'],
            'activeCounselors'      => $data['kpis']['activeCounselors'],
            'chatSessionsThisWeek'  => $data['kpis']['chatSessionsThisWeek'],

            // KPI trend labels
            'appointmentsTrend'     => $data['kpis']['appointmentsTrend'],
            'sessionsTrend'         => $data['kpis']['sessionsTrend'],

            // Lists
            'recentAppointments'    => $recentAppointments,
            'activities'            => $activities,
            'recentChatSessions'    => $recentChatSessions,
        ]);
    }

    /**
     * JSON for live refresh (poller).
     */
    public function stats(Request $request): JsonResponse
    {
        return response()
            ->json($this->buildStatsPayload())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /* ===================== Helpers ===================== */

    /**
     * Resolve which appointments table exists.
     */
    protected function resolveApptTable(): ?string
    {
        foreach (self::APPT_TABLE_CANDIDATES as $name) {
            if (Schema::hasTable($name)) {
                return $name;
            }
        }
        return null;
    }

    /**
     * Resolve the column used for “this week / last week” comparisons.
     */
    protected function resolveApptWeekColumn(string $table): string
    {
        foreach (self::APPT_WEEK_COL_CANDIDATES as $col) {
            if (Schema::hasColumn($table, $col)) {
                return $col;
            }
        }
        if (Schema::hasColumn($table, self::APPT_DATE_FALLBACK_COL)) {
            return self::APPT_DATE_FALLBACK_COL;
        }
        return 'created_at';
    }

    /**
     * Pick the best datetime for a single appointment row.
     */
    protected function pickApptWhen(array $row, string $table): ?string
    {
        foreach (['scheduled_at', 'appointment_at', 'datetime', 'created_at'] as $k) {
            if (!empty($row[$k])) {
                return Carbon::parse($row[$k])->toIso8601String();
            }
        }

        if (Schema::hasColumn($table, self::APPT_DATE_FALLBACK_COL) && !empty($row[self::APPT_DATE_FALLBACK_COL])) {
            $dt = \trim(($row[self::APPT_DATE_FALLBACK_COL] ?? '') . ' ' . ($row[self::APPT_TIME_FALLBACK_COL] ?? '00:00:00'));
            return Carbon::parse($dt)->toIso8601String();
        }

        return null;
    }

    /**
     * Compare week-over-week and return a human label.
     */
    protected function compareTrend(int $thisWeek, int $lastWeek): string
    {
        if ($lastWeek === 0 && $thisWeek > 0)  return '↑ Higher than last week';
        if ($lastWeek === 0 && $thisWeek === 0) return '= Same as last week';
        if ($thisWeek > $lastWeek)              return '↑ Higher than last week';
        if ($thisWeek < $lastWeek)              return '↓ Lower than last week';
        return '= Same as last week';
    }

    /**
     * Build the dashboard payload (used by both Blade + JSON).
     */
    protected function buildStatsPayload(): array
    {
        $now         = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek   = $now->copy()->endOfWeek();
        $lastStart   = $now->copy()->subWeek()->startOfWeek();
        $lastEnd     = $now->copy()->subWeek()->endOfWeek();

        /* ---------- KPIs: Appointments ---------- */
        $apptTable          = $this->resolveApptTable();
        $appointmentsTotal  = $apptTable ? DB::table($apptTable)->count() : 0;
        $apptWeekCol        = $apptTable ? $this->resolveApptWeekColumn($apptTable) : null;
        $apptsThisWeek      = 0;
        $apptsLastWeek      = 0;

        if ($apptTable && $apptWeekCol) {
            $apptsThisWeek = DB::table($apptTable)
                ->whereBetween($apptWeekCol, [$startOfWeek, $endOfWeek])
                ->count();

            $apptsLastWeek = DB::table($apptTable)
                ->whereBetween($apptWeekCol, [$lastStart, $lastEnd])
                ->count();
        }

        $appointmentsTrend = $this->compareTrend($apptsThisWeek, $apptsLastWeek);

        /* ---------- KPIs: Active Counselors ---------- */
        $activeCounselors = Schema::hasTable('tbl_counselors')
            ? DB::table('tbl_counselors')
                ->when(Schema::hasColumn('tbl_counselors', 'status'),   fn ($q) => $q->where('status', 'active'))
                ->when(Schema::hasColumn('tbl_counselors', 'is_active'), fn ($q) => $q->orWhere('is_active', 1))
                ->count()
            : 0;

        /* ---------- KPIs: Chat Sessions ---------- */
        $sessionsThisWeek = 0;
        $sessionsLastWeek = 0;

        if (Schema::hasTable('chat_sessions')) {
            $sessionsThisWeek = DB::table('chat_sessions')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->count();

            $sessionsLastWeek = DB::table('chat_sessions')
                ->whereBetween('created_at', [$lastStart, $lastEnd])
                ->count();
        }

        $sessionsTrend = $this->compareTrend($sessionsThisWeek, $sessionsLastWeek);

        /* ---------- KPI: Critical Cases (from chat_sessions) ---------- */
        // NOTE: This counts total HIGH-risk chat sessions (not distinct users).
        $criticalCasesTotal = 0;

        if (Schema::hasTable('chat_sessions')) {
            $criticalCasesTotal = DB::table('chat_sessions')
                ->where('risk_level', 'high')
                ->count();
        }

        /* ---------- Activities ---------- */
        $coalesceActor = function (string $table, string $alias): string {
            $parts = [];
            if (Schema::hasColumn($table, 'name'))      $parts[] = "$alias.name";
            if (Schema::hasColumn($table, 'full_name')) $parts[] = "$alias.full_name";

            $hasFirst = Schema::hasColumn($table, 'first_name');
            $hasLast  = Schema::hasColumn($table, 'last_name');
            if ($hasFirst && $hasLast)                  $parts[] = "CONCAT($alias.first_name,' ',$alias.last_name)";

            if (Schema::hasColumn($table, 'email'))     $parts[] = "$alias.email";

            $parts[] = "'User'";

            return 'COALESCE(' . \implode(', ', $parts) . ')';
        };

        $activities = collect();

        if (Schema::hasTable('chat_sessions')) {
            $cq = DB::table('chat_sessions as cs')
                ->orderByDesc('cs.created_at')
                ->limit(5);

            if (Schema::hasTable('tbl_registration')) {
                $cq->leftJoin('tbl_registration as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('tbl_registration', 'u');
            } elseif (Schema::hasTable('users')) {
                $cq->leftJoin('users as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('users', 'u');
            } else {
                $actorExpr = "'User'";
            }

            $chatActs = $cq->selectRaw("cs.created_at, cs.topic_summary, $actorExpr as actor_name")
                ->get()
                ->map(fn ($r) => [
                    'event'      => 'chat_session.started',
                    'actor'      => $r->actor_name,
                    'meta'       => $r->topic_summary,
                    'created_at' => Carbon::parse($r->created_at)->toIso8601String(),
                ]);

            $activities = $activities->merge($chatActs);
        }

        if (Schema::hasTable('tbl_registration')) {
            $regActs = DB::table('tbl_registration')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($r) {
                    $name    = \trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                    $display = $name !== '' ? $name : ($r->email ?? 'User');

                    return [
                        'event'      => 'user.registered',
                        'actor'      => $display,
                        'meta'       => null,
                        'created_at' => Carbon::parse($r->created_at)->toIso8601String(),
                    ];
                });

            $activities = $activities->merge($regActs);
        }

        $activities = $activities
            ->sortByDesc('created_at')
            ->values()
            ->take(5)
            ->all();

        /* ---------- Recent Appointments (NEWEST FIRST) ---------- */
        $recentAppointments = [];

        if ($apptTable) {
            $orderCol = Schema::hasColumn($apptTable, 'created_at') ? 'created_at' : null;

            if (!$orderCol) {
                if (Schema::hasColumn($apptTable, 'id')) {
                    $orderCol = 'id';
                } else {
                    foreach (['scheduled_at', 'appointment_at', 'datetime'] as $c) {
                        if (Schema::hasColumn($apptTable, $c)) {
                            $orderCol = $c;
                            break;
                        }
                    }
                }
            }

            $q = DB::table($apptTable);
            if ($orderCol) {
                $q->orderByDesc($orderCol);
            }

            $rows = $q->limit(5)->get();

            foreach ($rows as $r) {
                $arr = (array) $r;
                $recentAppointments[] = [
                    'id'           => $arr['id']           ?? null,
                    'status'       => $arr['status']       ?? ($arr['state'] ?? 'scheduled'),
                    'when'         => $this->pickApptWhen($arr, $apptTable),
                    'student_id'   => $arr['student_id']   ?? null,
                    'counselor_id' => $arr['counselor_id'] ?? null,
                    'notes'        => $arr['notes']        ?? null,
                ];
            }
        }

        /* ---------- Recent Chat Sessions ---------- */
        $recentChatSessions = [];

        if (Schema::hasTable('chat_sessions')) {
            $cq = DB::table('chat_sessions as cs')
                ->orderByDesc('cs.created_at')
                ->limit(5);

            if (Schema::hasTable('tbl_registration')) {
                $cq->leftJoin('tbl_registration as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('tbl_registration', 'u');
            } elseif (Schema::hasTable('users')) {
                $cq->leftJoin('users as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('users', 'u');
            } else {
                $actorExpr = "'User'";
            }

            $recentChatSessions = $cq
                ->selectRaw("cs.created_at, cs.topic_summary, cs.risk_level, $actorExpr as actor_name")
                ->get()
                ->map(fn ($r) => [
                    'created_at'    => Carbon::parse($r->created_at)->toIso8601String(),
                    'topic_summary' => $r->topic_summary ?: 'Starting conversation…',
                    'risk_level'    => $r->risk_level ?? 'low',
                    'actor'         => $r->actor_name,
                ])
                ->all();
        }

        return [
            'kpis' => [
                'appointmentsTotal'    => $appointmentsTotal,
                'criticalCasesTotal'   => $criticalCasesTotal, // based on HIGH-risk sessions (total count)
                'activeCounselors'     => $activeCounselors,
                'chatSessionsThisWeek' => $sessionsThisWeek,
                'appointmentsTrend'    => $appointmentsTrend,
                'sessionsTrend'        => $sessionsTrend,
            ],
            'recentAppointments' => $recentAppointments,
            'activities'         => $activities,
            'recentChatSessions' => $recentChatSessions,
            'generatedAt'        => $now->toIso8601String(),
        ];
    }
}
