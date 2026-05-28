<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\SuperAdminSeeder;

class SeedSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'super-admin:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the initial super admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding super admin...');

        $seeder = new SuperAdminSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        return Command::SUCCESS;
    }
}
