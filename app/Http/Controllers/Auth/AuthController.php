<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Preference\Preference;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return ApiResponse::error(
                "Too many login attempts. Please try again in {$seconds} seconds.",
                429
            );
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Verify user has access to this tenant
        // For now, we check if user's primary tenant matches or if tenant is active
        $tenant = \App\Models\Tenant::find($request->tenant_id);

        if (! $tenant || ! $tenant->is_active) {
            throw ValidationException::withMessages([
                'tenant_id' => ['The selected tenant is invalid or inactive.'],
            ]);
        }

        // Check if employee is active (prevent terminated employees from logging in)
        $employee = \App\Models\Hris\Employee::where('user_id', $user->id)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if ($employee && ! $employee->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact HR for assistance.'],
            ]);
        }

        // Check password expiry
        $expiryDays = (int) Preference::getValue('security_policy', 'password_expiry_days', $request->tenant_id, null, 0);

        if ($expiryDays > 0 && $user->password_changed_at) {
            // Calculate absolute days from password change to now
            $daysSinceChange = abs(now()->diffInDays($user->password_changed_at, false));

            if ($daysSinceChange >= $expiryDays) {
                return ApiResponse::error(
                    'Password expired',
                    403,
                    [
                        'password_expired' => true,
                        'email' => $user->email,
                        'tenant_id' => $request->tenant_id,
                        'days_overdue' => $daysSinceChange - $expiryDays,
                    ]
                );
            }
        }

        // Check for 2FA enforcement or individual enabling
        $is2faEnforced = Preference::getValue('security_policy', 'enforce_2fa', $request->tenant_id, null, false) === true;
        $is2faEnabled = $user->two_factor_enabled || $is2faEnforced;

        if ($is2faEnabled) {
            // Generate 6-digit code
            $code = rand(100000, 999999);
            $user->update([
                'two_factor_code' => $code,
                'two_factor_expires_at' => now()->addMinutes(10),
            ]);

            // Clear any existing tokens for this user to be safe
            // $user->tokens()->where('tenant_id', $request->tenant_id)->delete();

            // Send Notification
            $user->notify(new \App\Notifications\TwoFactorCodeNotification($code));

            return ApiResponse::success([
                'two_factor_required' => true,
                'email' => $user->email,
            ], 'Verification code sent to your email');
        }

        $isNewDevice = !$user->knownDevices()
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->userAgent())
            ->exists();

        // Cleanup existing tokens for the same device/IP combination to keep sessions unique
        $user->tokens()
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->userAgent())
            ->delete();

        // Create tenant-scoped token
        $token = $user->createToken('auth-token', ['*'], $request->tenant_id);

        // Update or Create known device record
        try {
            $deviceName = parse_user_agent($request->userAgent());
            $user->knownDevices()->updateOrCreate(
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                [
                    'device_name' => $deviceName,
                    'last_login_at' => now(),
                ]
            );

            // Notify if new device
            if ($isNewDevice) {
                $user->notify(new \App\Notifications\NewDeviceLoginNotification($deviceName, $request->ip()));
            }
        } catch (\Exception $e) {
            Log::error('Failed to update known device or send notification: ' . $e->getMessage());
        }

        // Update last login - preserve previous login time
        // Only update here IF 2FA is NOT enabled. If 2FA is enabled, update happens in verify2fa.
        if (!$is2faEnabled) {
            $user->update([
                'previous_login' => $user->last_login,
                'last_login' => now(),
                'last_login_ip' => $request->ip(),
            ]);
        }

        return ApiResponse::success([
            'user' => $user->load(['tenant', 'employee']),
            'tenant' => $user->tenant,
            'token' => $token->plainTextToken,
        ], 'Login successful');
    }

    /**
     * Verify 2FA code
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || $user->two_factor_code !== $request->code) {
            return ApiResponse::error('Invalid verification code', 422);
        }

        if (now()->greaterThan($user->two_factor_expires_at)) {
            return ApiResponse::error('Verification code has expired', 422);
        }

        // Clear the code
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        // Check if this is a new device (not seen before for this user/IP combo)
        $isNewDevice = !$user->knownDevices()
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->userAgent())
            ->exists();

        // Cleanup existing tokens for the same device/IP combination to keep sessions unique
        $user->tokens()
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->userAgent())
            ->delete();

        // Create tenant-scoped token
        $token = $user->createToken('auth-token', ['*'], $request->tenant_id);

        // Update or Create known device record
        try {
            $deviceName = parse_user_agent($request->userAgent());
            $user->knownDevices()->updateOrCreate(
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                [
                    'device_name' => $deviceName,
                    'last_login_at' => now(),
                ]
            );

            // Notify if new device
            if ($isNewDevice) {
                $user->notify(new \App\Notifications\NewDeviceLoginNotification($deviceName, $request->ip()));
            }
        } catch (\Exception $e) {
            Log::error('Failed to update known device or send notification: ' . $e->getMessage());
        }

        // Update last login - preserve previous login time
        // This is where we update if 2FA was used
        $user->update([
            'previous_login' => $user->last_login,
            'last_login' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return ApiResponse::success([
            'user' => $user->load(['tenant', 'employee']),
            'tenant' => $user->tenant,
            'token' => $token->plainTextToken,
        ], '2FA verification successful');
    }

    /**
     * Update user security settings (e.g. 2FA)
     */
    public function updateSecuritySettings(Request $request)
    {
        $request->validate([
            'two_factor_enabled' => 'nullable|boolean',
        ]);

        $user = $request->user();

        if ($request->has('two_factor_enabled')) {
            $user->update([
                'two_factor_enabled' => $request->two_factor_enabled,
            ]);
        }

        return ApiResponse::success($user, 'Security settings updated successfully');
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'string', 'same:new_password'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('The provided password does not match your current password.', 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        return ApiResponse::success(null, 'Password changed successfully');
    }

    /**
     * Reset expired password (public endpoint)
     */
    public function resetExpiredPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'string', 'same:new_password'],
        ]);

        $user = User::where('email', $request->email)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if (!$user) {
            return ApiResponse::error('User not found in this organization.', 404);
        }

        // Verify user belongs to the tenant
        $employee = \App\Models\Hris\Employee::where('user_id', $user->id)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if (!$employee) {
            return ApiResponse::error('User not found in this organization.', 404);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('The provided current password does not match.', 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        return ApiResponse::success(null, 'Password has been updated successfully. You can now login.');
    }

    /**
     * Get active sessions
     */
    public function getSessions(Request $request)
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()->id;

        // Group tokens by IP and User-Agent to show unique "Active Sessions"
        // We take the most recent token for each unique combination
        $sessions = $user->tokens()
            ->select('id', 'name', 'ip_address', 'user_agent', 'last_used_at', 'created_at')
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->groupBy(function ($token) {
                return $token->ip_address . '-' . $token->user_agent;
            })
            ->map(function ($group) use ($currentTokenId) {
                $token = $group->first(); // The most recently used one in the group
                $isCurrent = $group->contains('id', $currentTokenId);

                return [
                    'id' => $token->id,
                    'device' => $token->name,
                    'ip_address' => $token->ip_address,
                    'user_agent' => $token->user_agent,
                    'last_active' => $token->last_used_at ? $token->last_used_at->toDateTimeString() : $token->created_at->toDateTimeString(),
                    'is_current' => $isCurrent,
                    'tokens_in_session' => $group->pluck('id'), // Helpful for revoking the whole "session"
                ];
            })
            ->values();

        return ApiResponse::success($sessions);
    }

    /**
     * Revoke a specific session (group of tokens for a device)
     */
    public function revokeSession(Request $request, $id)
    {
        $user = $request->user();
        $targetToken = $user->tokens()->find($id);

        if (!$targetToken) {
            return ApiResponse::notFound('Session not found');
        }

        // Prevent revoking current session via this endpoint (should use logout)
        if ($targetToken->id === $user->currentAccessToken()->id) {
            return ApiResponse::error('Cannot revoke current session. Please use logout instead.', 422);
        }

        // Revoke all tokens that match this device/IP combination
        $user->tokens()
            ->where('ip_address', $targetToken->ip_address)
            ->where('user_agent', $targetToken->user_agent)
            ->delete();

        return ApiResponse::success(null, 'Session revoked successfully');
    }

    /**
     * Revoke all sessions except the current one
     */
    public function revokeOtherSessions(Request $request)
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()->id;

        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return ApiResponse::success(null, 'All other sessions revoked successfully');
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Revoke current token
        $user->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('employee');

        return ApiResponse::success($user);
    }

    /**
     * Handle user registration
     * DISABLED: Employees should be created through HRIS system
     */
    public function register(Request $request)
    {
        return ApiResponse::forbidden('Public registration is disabled. Please contact your administrator to create an employee account.');
    }

    /*
    // Original register method - disabled for security
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }
    */
}
