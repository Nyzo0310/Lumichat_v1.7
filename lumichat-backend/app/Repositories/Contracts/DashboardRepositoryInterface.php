<?php

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    /**
     * Build the complete dashboard payload (used by Blade + JSON).
     *
     * Returns:
     * [
     *   'kpis' => [
     *      'appointmentsTotal'    => int,
     *      'criticalCasesTotal'   => int,
     *      'activeCounselors'     => int,
     *      'chatSessionsThisWeek' => int,
     *      'appointmentsTrend'    => string,
     *      'sessionsTrend'        => string,
     *   ],
     *   'recentAppointments' => array<array>,
     *   'activities'         => array<array>,
     *   'recentChatSessions' => array<array>,
     *   'generatedAt'        => string ISO8601,
     * ]
     */
    public function stats(): array;
}
