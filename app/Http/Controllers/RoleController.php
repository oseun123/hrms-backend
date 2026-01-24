<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles for the current tenant.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $roles = Role::where('tenant_id', $tenantId)
            ->with('permissions')
            ->withCount('users')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Display the specified role with its permissions and assigned users.
     */
    public function show(Request $request, Role $role)
    {
        $tenantId = $request->user()->tenant_id;

        if ($role->tenant_id != $tenantId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $role->load(['permissions', 'users.employee' => function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }])
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = DB::transaction(function () use ($validated, $tenantId) {
            $role = Role::create([
                'tenant_id' => $tenantId,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_deletable' => true,
                'is_default' => false,
            ]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            return $role;
        });

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ], 201);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        $tenantId = $request->user()->tenant_id;

        if ($role->tenant_id != $tenantId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::transaction(function () use ($validated, $role) {
            $role->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
            ]);

            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role->load('permissions')
        ]);
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Request $request, Role $role)
    {
        $tenantId = $request->user()->tenant_id;

        if ($role->tenant_id != $tenantId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$role->is_deletable) {
            return response()->json(['message' => 'This role is protected and cannot be deleted.'], 403);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Sync roles for a specific user.
     */
    public function syncUserRoles(Request $request, \App\Models\User $user)
    {
        $tenantId = $request->user()->tenant_id;

        if ($user->tenant_id != $tenantId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->roles()->sync($validated['roles']);

        return response()->json([
            'success' => true,
            'message' => 'User roles updated successfully',
            'data' => $user->load('roles')
        ]);
    }
    /**
     * Sync users for a specific role.
     */
    public function assignUsers(Request $request, Role $role)
    {
        $tenantId = $request->user()->tenant_id;

        if ($role->tenant_id != $tenantId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        // Ensure all users belong to the same tenant
        $usersCount = \App\Models\User::whereIn('id', $validated['users'])
            ->where('tenant_id', $tenantId)
            ->count();

        if ($usersCount !== count($validated['users'])) {
            return response()->json(['message' => 'Some users are invalid or belong to another tenant.'], 422);
        }

        $role->users()->sync($validated['users']);

        return response()->json([
            'success' => true,
            'message' => 'Role assignments updated successfully',
            'data' => $role->load(['users.employee' => function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }])
        ]);
    }
}
