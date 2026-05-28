<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Register Super Admin Routes
            // Using a minimal middleware stack WITHOUT EnsureFrontendRequestsAreStateful
            // because super-admin uses Bearer tokens, not session cookies.
            // This avoids CSRF mismatch errors when accessed from localhost:3000 (no tenant subdomain).
            Route::middleware([
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                \App\Http\Middleware\ExtractTokenFromQuery::class,
            ])
                ->prefix('api')
                ->group(base_path('routes/super-admin.php'));

            // Custom route model binding for Employee with tenant filtering
            Route::bind('employee', function ($value) {
                $employee = \App\Models\Hris\Employee::where('id', $value);

                // If user is authenticated, filter by tenant
                if (auth()->check() && auth()->user()->tenant_id) {
                    $employee->where('tenant_id', auth()->user()->tenant_id);
                }

                return $employee->firstOrFail();
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware
        $middleware->use([
            \App\Http\Middleware\ForceCors::class, // Custom CORS handler
            \App\Http\Middleware\TrustProxies::class,
            // \Illuminate\Http\Middleware\HandleCors::class, // Disabled in favor of ForceCors
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // Web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ExtractTokenFromQuery::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\InjectTenantId::class,
            \App\Http\Middleware\SetRegionalPreferences::class,
        ]);

        // Throttle API requests - DISABLED until rate limiter is properly configured
        // $middleware->throttleApi();

        // Route middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'approval' => \App\Http\Middleware\CheckApprovalRequired::class,
            'track.first.login' => \App\Http\Middleware\TrackFirstLogin::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'auth.super-admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Helpers\ApiResponse::notFound('Resource not found');
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return \App\Helpers\ApiResponse::notFound('Resource not found');
            }
        });
    })
    ->create();
