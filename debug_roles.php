<?php

use App\Models\User;
use App\Models\Role;

echo "--- DEBUG START ---\n";

$users = User::all();
echo "Total Users: " . $users->count() . "\n";
foreach ($users as $user) {
    echo "User: {$user->email} (ID: {$user->id}, Tenant: {$user->tenant_id})\n";
    echo "  Roles: " . $user->roles()->count() . "\n";
}

echo "\n--- ROLES ---\n";
$roles = Role::all();
echo "Total Roles: " . $roles->count() . "\n";
foreach ($roles as $role) {
    echo "Role: {$role->name} (Slug: {$role->slug}, ID: {$role->id}, Tenant: {$role->tenant_id})\n";
}

echo "\n--- CHECKS ---\n";
$adminRole = Role::where('slug', 'admin')->first();
echo "Admin Role Found: " . ($adminRole ? 'Yes' : 'No') . "\n";

$employeeRole = Role::where('slug', 'employee')->first();
echo "Employee Role Found: " . ($employeeRole ? 'Yes' : 'No') . "\n";

echo "--- DEBUG END ---\n";
