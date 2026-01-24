<?php

namespace App\Console\Commands;

use App\Models\Hris\Employee;
use App\Services\ProfileCompletenessService;
use Illuminate\Console\Command;

class RecalculateProfileCompleteness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profile:recalculate {--employee_id= : Recalculate for specific employee}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate profile completeness for all employees or a specific employee';

    protected $completenessService;

    /**
     * Create a new command instance.
     */
    public function __construct(ProfileCompletenessService $completenessService)
    {
        parent::__construct();
        $this->completenessService = $completenessService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->option('employee_id');

        if ($employeeId) {
            // Recalculate for specific employee
            $employee = Employee::find($employeeId);

            if (! $employee) {
                $this->error("Employee with ID {$employeeId} not found.");

                return 1;
            }

            $this->info("Recalculating profile completeness for employee: {$employee->full_name}");
            $this->completenessService->calculate($employee);
            $this->info('✓ Completed!');
        } else {
            // Recalculate for all employees
            $employees = Employee::with([
                'employmentDetails',
                'contactDetails',
                'financialDetails',
                'medicalDetails',
                'addresses',
                'education',
                'documents.documentType',
            ])->get();

            $this->info("Recalculating profile completeness for {$employees->count()} employees...");

            $bar = $this->output->createProgressBar($employees->count());
            $bar->start();

            foreach ($employees as $employee) {
                $this->completenessService->calculate($employee);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('✓ All profile completeness records updated!');
        }

        return 0;
    }
}
