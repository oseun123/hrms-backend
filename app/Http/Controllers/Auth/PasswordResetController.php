<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Handle password reset request
     */
    public function requestReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return ApiResponse::notFound('We could not find a user with that email address.');
        }

        // Generate reset token
        $token = Str::random(64);

        // Store token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'tenant_id' => 1, // Default tenant for now, will be dynamic with multi-tenancy
                'user_id' => $user->id,
                'email' => $request->email,
                'token' => Hash::make($token),
                'expires_at' => now()->addHour(), // Token expires in 1 hour
                'used_at' => null, // Reset used_at field for new token
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Send email notification using Notification facade
        $user->notify(new \App\Notifications\PasswordResetNotification($token, $request->email, $user->tenant));

        return ApiResponse::success([
            'email' => $request->email,
        ], 'Password reset instructions sent to your email.');
    }

    /**
     * Handle password reset
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Get the token record
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $resetRecord) {
            return ApiResponse::error('Invalid reset token.', 400);
        }

        // Verify token
        if (! Hash::check($request->token, $resetRecord->token)) {
            return ApiResponse::error('Invalid reset token.', 400);
        }

        // Check if token is expired
        if (now()->isAfter($resetRecord->expires_at)) {
            return ApiResponse::error('Reset token has expired.', 400);
        }

        // Check if token was already used
        if ($resetRecord->used_at) {
            return ApiResponse::error('Reset token has already been used.', 400);
        }

        // Reset the password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Mark token as used instead of deleting
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->update(['used_at' => now()]);

        // Revoke all tokens
        $user->tokens()->delete();

        return ApiResponse::success(null, 'Password has been reset successfully.');
    }
}
