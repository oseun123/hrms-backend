<?php

namespace App\Http\Controllers\SuperAdmin\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\SuperAdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SuperAdminAuthController extends Controller
{
    /**
     * Super admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'super-admin-login:' . Str::lower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return ApiResponse::error("Too many login attempts. Please try again in {$seconds} seconds.", 429);
        }

        $superAdmin = SuperAdmin::where('email', $request->email)->first();

        if (!$superAdmin || !Hash::check($request->password, $superAdmin->password)) {
            RateLimiter::hit($throttleKey);
            return ApiResponse::error('The provided credentials are incorrect.', 401);
        }

        RateLimiter::clear($throttleKey);

        // Revoke existing tokens for this device
        $superAdmin->tokens()
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->userAgent())
            ->delete();

        $token = $superAdmin->createToken('super-admin-token', ['*']);

        // Update last login
        $superAdmin->update(['last_login' => now()]);

        // Log the activity
        SuperAdminActivityLog::create([
            'super_admin_id' => $superAdmin->id,
            'action'         => 'auth.login',
            'description'    => "Super admin logged in from {$request->ip()}",
        ]);

        return ApiResponse::success([
            'super_admin' => $superAdmin,
            'token'       => $token->plainTextToken,
        ], 'Login successful');
    }

    /**
     * Super admin logout
     */
    public function logout(Request $request)
    {
        $superAdmin = auth('super-admin')->user();

        if ($superAdmin) {
            $request->user()->currentAccessToken()->delete();

            SuperAdminActivityLog::create([
                'super_admin_id' => $superAdmin->id,
                'action'         => 'auth.logout',
                'description'    => 'Super admin logged out',
            ]);
        }

        return ApiResponse::success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated super admin
     */
    public function me(Request $request)
    {
        return ApiResponse::success(auth('super-admin')->user());
    }
}
