<?php

namespace App\Traits;

use DateTimeInterface;

trait HasRegionalSettings
{
    /**
     * Prepare a date for array / JSON serialization.
     * Use the preferred date format from config if available.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        $timezone = config('app.timezone', 'UTC');
        $format = config('app.date_format', 'Y-m-d H:i:s');

        // If it's a date-only column (no time), adjust format and skip TZ conversion
        if ($date->format('H:i:s') === '00:00:00') {
            // Convert JS/Standard formats (DD/MM/YYYY) to PHP formats
            $phpFormat = str_replace(
                ['DD', 'MM', 'YYYY', 'YYYY-MM-DD', 'DD/MM/YYYY', 'MM/DD/YYYY', 'DD MMM YYYY'],
                ['d', 'm', 'Y', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y'],
                $format
            );

            // Clean up any double spaces or leftover time formats if the format was a full date + time string
            $phpFormat = trim(explode(' ', $phpFormat)[0]);

            return $date->format($phpFormat);
        }

        // Convert to preferred timezone for fields with time
        if ($timezone && $timezone !== 'UTC') {
            $date = \Carbon\Carbon::instance($date)->setTimezone($timezone);
        }

        return $date->format($format);
    }

    /**
     * Get the formatted currency value if the model has a currency-related field.
     * This is a helper accessor.
     */
    public function getFormattedAmountAttribute($value)
    {
        $symbol = config('app.currency_symbol', '₦');
        return $symbol . number_format($value, 2);
    }
}
