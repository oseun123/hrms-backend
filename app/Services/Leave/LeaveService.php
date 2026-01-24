<?php

namespace App\Services\Leave;

use App\Models\Preference\Preference;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveService
{
    /**
     * Calculate net leave days between two dates, accounting for weekends and holidays.
     * 
     * @param int $tenantId
     * @param string $startDate
     * @param string $endDate
     * @param bool $includeWeekends
     * @param bool $includeHolidays
     * @return float
     */
    public function calculateLeaveDays($tenantId, $startDate, $endDate, $includeWeekends = false, $includeHolidays = false)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return 0;
        }

        $period = CarbonPeriod::create($start, $end);
        $totalDays = 0;

        // Fetch working days from preferences
        $workingDaysPref = Preference::where('tenant_id', $tenantId)
            ->where('category', 'working_hours')
            ->get()
            ->keyBy('key');

        // Fetch holidays from preferences
        $holidaysPref = Preference::where('tenant_id', $tenantId)
            ->where('category', 'holidays')
            ->get();

        $holidays = [];
        foreach ($holidaysPref as $hp) {
            $val = $hp->value;
            if (isset($val['date'])) {
                // PublicHolidaySeeder saves as MM-DD
                $holidays[] = $val['date'];
            }
        }

        foreach ($period as $date) {
            $dayName = strtolower($date->format('l'));
            $isWeekend = !$this->isWorkingDay($workingDaysPref, $dayName);
            $isHoliday = in_array($date->format('m-d'), $holidays);

            if ($isWeekend && !$includeWeekends) {
                continue;
            }

            if ($isHoliday && !$includeHolidays) {
                // If the holiday preference has a special "counts_as_leave" flag, we check it here
                // For now, if not including holidays, we skip them
                continue;
            }

            $totalDays++;
        }

        return (float) $totalDays;
    }

    /**
     * Check if a day is a working day based on preferences.
     */
    protected function isWorkingDay($prefs, $dayName)
    {
        $key = "{$dayName}_enabled";
        if (isset($prefs[$key])) {
            return (bool) $prefs[$key]->value;
        }

        // Default to Mon-Fri if not set
        return !in_array($dayName, ['saturday', 'sunday']);
    }
}
