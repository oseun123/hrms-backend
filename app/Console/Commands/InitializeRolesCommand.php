<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:initialize-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign default Employee role to all users and Admin tool to specific test user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();
        $this->info('Found ' . $users->count() . ' users to process.');

        foreach ($users as $user) {
            $this->info("Processing user: {$user->email}");

            // 1. Assign 'Employee' role if they have no roles
            $employeeRole = Role::where('tenant_id', $user->tenant_id)
                ->where('slug', 'employee')
                ->first();

            if ($employeeRole) {
                if ($user->roles()->count() === 0) {
                    $user->roles()->attach($employeeRole->id);
                    $this->line(" - Assigned 'Employee' role.");
                } else {
                    $this->line(" - User already has roles.");
                }
            } else {
                $this->error(" - 'Employee' role not found for tenant {$user->tenant_id}");
            }

            // 2. Assign 'Admin' role to oseun04@gmail.com
            if ($user->email === 'oseun04@gmail.com') {
                $adminRole = Role::where('tenant_id', $user->tenant_id)
                    ->where('slug', 'admin')
                    ->first();

                if ($adminRole) {
                    // Check if they already have it
                    if (!$user->roles()->where('slug', 'admin')->exists()) {
                        $user->roles()->attach($adminRole->id);
                        $this->info(" - Assigned 'Admin' role to test user.");
                    } else {
                        $this->line(" - Test user already has 'Admin' role.");
                    }
                } else {
                    $this->error(" - 'Admin' role not found for tenant {$user->tenant_id}");
                }
            }
        }

        $this->info('Initialization complete.');
    }
}
