<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'Dashboard: Personal', 'slug' => 'dashboard.personal'],
            ['name' => 'Dashboard: Organizational Structure', 'slug' => 'dashboard.org_structure'],
            ['name' => 'Dashboard: Analytics & Insights', 'slug' => 'dashboard.analytics'],
            ['name' => 'Dashboard: Employee Tracking', 'slug' => 'dashboard.tracking'],

            // Preferences
            ['name' => 'Preferences: Privacy & Security', 'slug' => 'preferences.privacy_security'],
            ['name' => 'Preferences: Organization Settings', 'slug' => 'preferences.org_settings'],
            ['name' => 'Preferences: Employee Numbering', 'slug' => 'preferences.numbering'],
            ['name' => 'Preferences: Language & Region', 'slug' => 'preferences.language_region'],
            ['name' => 'Preferences: Display Settings', 'slug' => 'preferences.display'],
            ['name' => 'Preferences: Roles & Permissions', 'slug' => 'preferences.roles_permissions'],

            // HRIS
            ['name' => 'HRIS: Employees', 'slug' => 'hris.employees'],
            ['name' => 'HRIS: Departments', 'slug' => 'hris.departments'],
            ['name' => 'HRIS: Levels', 'slug' => 'hris.levels'],
            ['name' => 'HRIS: Grades', 'slug' => 'hris.grades'],
            ['name' => 'HRIS: Positions', 'slug' => 'hris.positions'],
            ['name' => 'HRIS: Skills', 'slug' => 'hris.skills'],
            ['name' => 'HRIS: Approvals', 'slug' => 'hris.approvals'],
            ['name' => 'HRIS: Reports', 'slug' => 'hris.reports'],

            // Leave Management
            ['name' => 'Leave: Dashboard', 'slug' => 'leave.dashboard'],
            ['name' => 'Leave: Approval Queue', 'slug' => 'leave.approval_queue'],
            ['name' => 'Leave: Configuration', 'slug' => 'leave.configuration'],
            ['name' => 'Leave: Organization Calendar', 'slug' => 'leave.org_calendar'],
            ['name' => 'Leave: Department Calendar', 'slug' => 'leave.dept_calendar'],
            ['name' => 'Leave: Reports', 'slug' => 'leave.reports'],
            ['name' => 'Leave: Balances', 'slug' => 'leave.balances'],

            // Payroll Management
            ['name' => 'Payroll: Dashboard', 'slug' => 'payroll.dashboard'],
            ['name' => 'Payroll: Monthly Processing', 'slug' => 'payroll.processing'],
            ['name' => 'Payroll: Leave Allowances', 'slug' => 'payroll.leave_allowances'],
            ['name' => 'Payroll: Annual Structures', 'slug' => 'payroll.annual_structures'],
            ['name' => 'Payroll: Configuration', 'slug' => 'payroll.setup'],
            ['name' => 'Payroll: Reports', 'slug' => 'payroll.reports'],

            // Performance Management
            ['name' => 'Performance: Dashboard', 'slug' => 'performance.dashboard'],
            ['name' => 'Performance: Configuration', 'slug' => 'performance.setup'],
            ['name' => 'Performance: My Deliverables / Appraisal', 'slug' => 'performance.my_deliverables'],
            ['name' => 'Performance: Team Deliverables', 'slug' => 'performance.team_deliverables'],
            ['name' => 'Performance: Employee Deliverables', 'slug' => 'performance.employee_deliverables'],
            ['name' => 'Performance: Appraisal Management', 'slug' => 'performance.appraisal_management'],
            ['name' => 'Performance: Reports', 'slug' => 'performance.reports'],

            // Request Management
            ['name' => 'Requests: Dashboard', 'slug' => 'requests.dashboard'],
            ['name' => 'Requests: Templates', 'slug' => 'requests.templates'],
            ['name' => 'Requests: Approvals', 'slug' => 'requests.approvals'],
            ['name' => 'Requests: Configuration', 'slug' => 'requests.configuration'],
            ['name' => 'Requests: Reports', 'slug' => 'requests.reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        // For each tenant, create default roles
        Tenant::all()->each(function ($tenant) {
            $this->createDefaultRoles($tenant);
        });
    }

    public function createDefaultRoles(Tenant $tenant)
    {
        // Admin Role
        $adminRole = Role::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Full administrative access',
                'is_deletable' => false,
                'is_default' => false,
            ]
        );
        $adminRole->permissions()->sync(Permission::all());

        // Employee Role
        $employeeRole = Role::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'employee'],
            [
                'name' => 'Employee',
                'description' => 'Default access for employees',
                'is_deletable' => false,
                'is_default' => true,
            ]
        );

        $employeePermissions = Permission::whereIn('slug', [
            'dashboard.personal',
            'preferences.privacy_security',
            'leave.dashboard',
            'leave.dept_calendar',
            'payroll.dashboard',
            'performance.dashboard',
            'performance.my_deliverables',
            'requests.dashboard',
            'requests.templates',
        ])->get();

        $employeeRole->permissions()->sync($employeePermissions);
    }
}
