<?php

namespace App\Providers;

use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeEmploymentDetail;
use App\Models\Hris\EmployeeFinancialDetail;
use App\Observers\EmployeeObserver;
use App\Observers\EmployeeHistoryObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Fix for MySQL "Specified key was too long" error
        Schema::defaultStringLength(191);

        // Use custom personal access token model
        \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        // Register observers
        Employee::observe(EmployeeObserver::class);
        EmployeeEmploymentDetail::observe(EmployeeHistoryObserver::class);
        EmployeeFinancialDetail::observe(EmployeeHistoryObserver::class);

        // Configure API rate limiting
        \Illuminate\Support\Facades\RateLimiter::for('api', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(300)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
