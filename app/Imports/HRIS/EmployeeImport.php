<?php

namespace App\Imports\HRIS;

use App\Models\Hris\Department;
use App\Models\Hris\Position;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeEmploymentDetail;
use App\Models\Hris\EmployeeOnboardingStatus;
use App\Models\User;
use App\Models\Role;
use App\Notifications\WelcomeEmployee;
use App\Services\EmployeeNumberService;
use App\Services\ProfileCompletenessService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements ToCollection, WithHeadingRow
{
    private $totalRows = 0;
    private $successCount = 0;
    private $failedCount = 0;
    private $errors = [];
    private $employeeNumberService;
    private $completenessService;

    public function __construct()
    {
        $this->employeeNumberService = app(EmployeeNumberService::class);
        $this->completenessService = app(ProfileCompletenessService::class);
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();
        $tenantId = Auth::user()->tenant_id;
        $createdBy = Auth::user()->id;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for 0-indexed, +1 for heading row

            // Skip if row is essentially empty (avoids validation errors on empty rows)
            if (empty($row['personal_email']) && empty($row['first_name']) && empty($row['last_name'])) {
                $this->totalRows--; // Adjust total count if we skip
                continue;
            }

            DB::beginTransaction();
            $isNewUserCreated = false;
            try {
                $this->processRow($row, $tenantId, $createdBy, $isNewUserCreated);
                DB::commit();
                $this->successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->failedCount++;

                // Manual cleanup fallback for non-transactional engines (e.g. MyISAM)
                // We ONLY clean up if we actually successfully created a User record in this iteration
                if ($isNewUserCreated) {
                    $this->manualCleanup($row['personal_email'] ?? null, $tenantId);
                }

                $this->errors[] = [
                    'row' => $rowNumber,
                    'email' => $row['personal_email'] ?? 'N/A',
                    'error' => $e->getMessage()
                ];
                Log::warning("Bulk import error at row {$rowNumber}: " . $e->getMessage());
            }
        }
    }

    private function processRow($row, $tenantId, $createdBy, &$isNewUserCreated)
    {
        // 1. Basic Validation
        if (empty($row['personal_email']) || empty($row['first_name']) || empty($row['last_name'])) {
            throw new \Exception("First Name, Last Name, and Personal Email are required.");
        }

        if (User::where('email', $row['personal_email'])->exists()) {
            throw new \Exception("User with email {$row['personal_email']} already exists.");
        }

        // 2. Smart Resolution
        $departmentId = $this->resolveDepartment($row['department'], $tenantId, $createdBy);
        $positionId = $this->resolvePosition($row['position'], $tenantId, $createdBy, $departmentId);
        $managerId = $this->resolveManager($row['manager_email'], $tenantId);

        // 3. Create User
        $temporaryPassword = 'Temp' . rand(1000, 9999) . '!';
        $user = User::create([
            'name' => trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']),
            'email' => $row['personal_email'],
            'password' => bcrypt($temporaryPassword),
            'tenant_id' => $tenantId,
        ]);
        $isNewUserCreated = true;

        // Assign default 'Employee' role
        $employeeRole = Role::where('tenant_id', $tenantId)
            ->where('slug', 'employee')
            ->first();

        if ($employeeRole) {
            $user->roles()->attach($employeeRole->id);
        }

        // 4. Generate Employee Number
        $employeeNumber = $this->employeeNumberService->generateNextNumber($tenantId);

        // 5. Create Employee
        $employee = Employee::create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'employee_number' => $employeeNumber,
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'],
            'date_of_birth' => $this->parseDate($row['date_of_birth_yyyy_mm_dd'] ?? null),
            'gender' => strtolower($row['gender'] ?? ''),
            'marital_status' => strtolower($row['marital_status'] ?? ''),
            'nationality' => $row['nationality'] ?? null,
            'national_id' => $row['national_id'] ?? null,
            'passport_number' => $row['passport_number'] ?? null,
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        // 5.5 Validate Employment Required Fields
        $workEmail = $row['work_email'] ?? $row['personal_email']; // Default to personal if work is empty
        if (EmployeeEmploymentDetail::where('work_email', $workEmail)->exists()) {
            throw new \Exception("Work email {$workEmail} is already in use by another employee.");
        }

        // 6. Create Employment Details
        $hireDate = $this->parseDate($row['hire_date_yyyy_mm_dd'] ?? null);
        if (!$hireDate) {
            throw new \Exception("Hire Date (YYYY-MM-DD) is required and must be a valid date.");
        }

        EmployeeEmploymentDetail::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'work_email' => $workEmail,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'manager_id' => $managerId,
            'employment_type' => strtolower($row['employment_type'] ?? 'full-time'),
            'employment_status' => strtolower($row['employment_status'] ?? 'active'),
            'hire_date' => $hireDate,
            'probation_end_date' => $this->parseDate($row['probation_end_date_yyyy_mm_dd'] ?? null),
            'probation_status' => strtolower($row['probation_status'] ?? 'pending'),
            'notice_period_days' => (int)($row['notice_period_days'] ?? 0),
            'work_location' => $row['work_location'] ?? null,
            'shift' => strtolower($row['shift'] ?? ''),
            'work_schedule' => $row['work_schedule'] ?? null,
            'remote_work_eligible' => strtolower($row['remote_work_eligible_yesno'] ?? '') === 'yes',
        ]);

        // 7. Onboarding & Completion
        EmployeeOnboardingStatus::create([
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'user_created' => true,
            'welcome_email_sent' => true,
            'welcome_email_sent_at' => now(),
            'password_reset_sent' => true,
            'password_reset_sent_at' => now(),
        ]);

        $user->notify(new WelcomeEmployee($user->name, $employeeNumber, $temporaryPassword));
        $this->completenessService->calculate($employee);
    }

    private function resolveDepartment($name, $tenantId, $createdBy)
    {
        if (empty($name)) return null;

        $dept = Department::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if (!$dept) {
            // Generate a code from the name (e.g. "Human Resources" -> "HUMAN-RESOURCES")
            $code = strtoupper(str_replace(' ', '-', trim($name)));

            // Check if code exists, if so append random
            if (Department::where('tenant_id', $tenantId)->where('code', $code)->exists()) {
                $code .= '-' . rand(100, 999);
            }

            $dept = Department::create([
                'tenant_id' => $tenantId,
                'code' => $code,
                'name' => $name,
                'is_active' => true,
                'created_by' => $createdBy,
            ]);
        }

        return $dept->id;
    }

    private function resolvePosition($title, $tenantId, $createdBy, $departmentId)
    {
        if (empty($title)) return null;

        $pos = Position::where('tenant_id', $tenantId)
            ->where('title', $title)
            ->first();

        if (!$pos) {
            // If the position doesn't exist, we MUST have a department to create it
            if (!$departmentId) {
                throw new \Exception("A department is required to create a new position: \"{$title}\". Please ensure the department column is populated.");
            }

            // Generate a code from the title
            $code = strtoupper(str_replace(' ', '-', trim($title)));

            // Check if code exists
            if (Position::where('tenant_id', $tenantId)->where('code', $code)->exists()) {
                $code .= '-' . rand(100, 999);
            }

            $pos = Position::create([
                'tenant_id' => $tenantId,
                'department_id' => $departmentId,
                'code' => $code,
                'title' => $title,
                'is_active' => true,
                'created_by' => $createdBy,
            ]);
        }

        return $pos->id;
    }

    private function resolveManager($email, $tenantId)
    {
        if (empty($email)) return null;

        $manager = User::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        if (!$manager) return null;

        $employee = Employee::where('user_id', $manager->id)->first();
        return $employee ? $employee->id : null;
    }

    private function manualCleanup($email, $tenantId)
    {
        if (!$email) return;

        try {
            $user = User::where('tenant_id', $tenantId)->where('email', $email)->first();
            if ($user) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    // Delete details first
                    EmployeeEmploymentDetail::where('employee_id', $employee->id)->delete();
                    EmployeeOnboardingStatus::where('employee_id', $employee->id)->delete();

                    // Force delete employee because it uses soft deletes
                    $employee->forceDelete();
                }
                $user->delete();
                Log::info("Manual cleanup performed for failed import row: {$email}");
            }
        } catch (\Exception $e) {
            Log::error("Manual cleanup failed for {$email}: " . $e->getMessage());
        }
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;

        try {
            // Excel sometimes gives numeric dates
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }
    public function getSuccessCount()
    {
        return $this->successCount;
    }
    public function getFailedCount()
    {
        return $this->failedCount;
    }
    public function getErrors()
    {
        return $this->errors;
    }
}
