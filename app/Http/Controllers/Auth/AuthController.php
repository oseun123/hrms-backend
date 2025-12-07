<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Verify user has access to this tenant
        // For now, we check if user's primary tenant matches or if tenant is active
        $tenant = \App\Models\Tenant::find($request->tenant_id);

        if (!$tenant || !$tenant->is_active) {
            throw ValidationException::withMessages([
                'tenant_id' => ['The selected tenant is invalid or inactive.'],
            ]);
        }

        // TODO: Add user-tenant access control check here
        // For now, we allow access if tenant exists and is active

        // Create tenant-scoped token
        $token = $user->createToken('auth-token', ['*'], $request->tenant_id);

        return ApiResponse::success([
            'user' => $user->load('tenant'),
            'token' => $token->plainTextToken,
        ], 'Login successful');
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return ApiResponse::success($request->user());
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
