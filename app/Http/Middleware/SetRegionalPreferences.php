<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Preference\Preference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class SetRegionalPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Fetch language preferences for this user/tenant
            // User overides tenant
            $tenantPrefs = Preference::where('tenant_id', $tenantId)
                ->whereNull('user_id')
                ->where('category', 'language')
                ->get()
                ->keyBy('key');

            $userPrefs = Preference::where('user_id', $user->id)
                ->where('category', 'language')
                ->get()
                ->keyBy('key');

            $preferences = $tenantPrefs->merge($userPrefs);

            // Set Timezone
            if ($tz = $preferences->get('timezone')) {
                date_default_timezone_set($tz->value);
                Config::set('app.timezone', $tz->value);
            }

            // Set Date Format
            if ($df = $preferences->get('date_format')) {
                Config::set('app.date_format', $df->value);
            }

            // Set Currency
            if ($curr = $preferences->get('currency')) {
                // Map currency code to symbol
                $symbols = [
                    'NGN' => '₦',
                    'USD' => '$',
                    'EUR' => '€',
                    'GBP' => '£',
                    'ZAR' => 'R',
                    'KES' => 'KSh',
                    'GHS' => '₵',
                    'EGP' => 'E£',
                    'AED' => 'د.إ',
                ];
                $symbol = $symbols[$curr->value] ?? $curr->value;
                Config::set('app.currency_symbol', $symbol);
            }
        }

        return $next($request);
    }
}
