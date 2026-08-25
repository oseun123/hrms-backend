<?php

namespace App\Services;

use App\Models\Preference\Preference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LeaveYearService
{
    /**
     * Get the leave year start month for the current tenant.
     * Defaults to 1 (January) if not set.
     */
    public function getLeaveYearStartMonth(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? Auth::user()?->tenant_id;

        if (!$tenantId) {
            return 1; // Default to January
        }

        $month = Preference::getValue('leave', 'leave_year_start_month', $tenantId);

        return is_numeric($month) ? (int) $month : 1;
    }

    /**
     * Get the current leave year based on the configured start month.
     * Returns the year that represents the leave cycle.
     *
     * For example, if leave year starts in April:
     * - May 2025 belongs to leave year 2025 (April 2025 - March 2026)
     * - February 2025 belongs to leave year 2024 (April 2024 - March 2025)
     */
    public function getCurrentLeaveYear(?int $tenantId = null): int
    {
        return $this->getLeaveYearForDate(Carbon::now(), $tenantId);
    }

    /**
     * Get the leave year for a specific date.
     */
    public function getLeaveYearForDate(Carbon $date, ?int $tenantId = null): int
    {
        $startMonth = $this->getLeaveYearStartMonth($tenantId);

        // If we're before the start month, we're in the previous year's leave cycle
        if ($date->month < $startMonth) {
            return $date->year - 1;
        }

        return $date->year;
    }

    /**
     * Get the start and end dates for a specific leave year.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function getLeaveYearBoundaries(int $leaveYear, ?int $tenantId = null): array
    {
        $startMonth = $this->getLeaveYearStartMonth($tenantId);

        $start = Carbon::create($leaveYear, $startMonth, 1)->startOfDay();

        // End date is one day before the next leave year starts
        $end = Carbon::create($leaveYear + 1, $startMonth, 1)->subDay()->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Check if a date falls within a specific leave year.
     */
    public function isDateInLeaveYear(Carbon $date, int $leaveYear, ?int $tenantId = null): bool
    {
        $boundaries = $this->getLeaveYearBoundaries($leaveYear, $tenantId);

        return $date->between($boundaries['start'], $boundaries['end']);
    }

    /**
     * Get a human-readable label for a leave year.
     * For January start: "2025"
     * For other months: "2025/2026"
     */
    public function getLeaveYearLabel(int $leaveYear, ?int $tenantId = null): string
    {
        $startMonth = $this->getLeaveYearStartMonth($tenantId);

        if ($startMonth === 1) {
            return (string) $leaveYear;
        }

        return $leaveYear . '/' . ($leaveYear + 1);
    }

    /**
     * Get the current active leave year, taking year-end processing into account.
     */
    public function getCurrentActiveLeaveYear(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? Auth::user()?->tenant_id;
        if (!$tenantId) {
            return Carbon::now()->year;
        }

        $currentYear = $this->getCurrentLeaveYear($tenantId);
        $previousYear = $currentYear - 1;

        // Check if previous year has been processed
        $previousYearProcessed = \App\Models\Leave\LeaveYearEndProcessing::where('tenant_id', $tenantId)
            ->where('from_year', $previousYear)
            ->exists();

        if (!$previousYearProcessed) {
            return $previousYear;
        }

        return $currentYear;
    }
}

