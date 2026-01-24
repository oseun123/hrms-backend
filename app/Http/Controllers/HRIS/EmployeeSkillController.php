<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeSkill;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Services\ProfileCompletenessService;

class EmployeeSkillController extends Controller
{
    use HandlesApiErrors;

    protected $profileCompletenessService;

    public function __construct(ProfileCompletenessService $profileCompletenessService)
    {
        $this->profileCompletenessService = $profileCompletenessService;
    }

    /**
     * Display a listing of skills assigned to the employee
     */
    public function index(Request $request, Employee $employee)
    {
        try {
            $skills = $employee->skills()
                ->with('skill')
                ->where('tenant_id', $request->user()->tenant_id)
                ->get();

            return ApiResponse::success($skills);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching employee skills');
        }
    }

    /**
     * Assign a skill to the employee
     */
    public function store(Request $request, Employee $employee)
    {
        try {
            $validated = $request->validate([
                'skill_id' => 'required|exists:skills,id',
                'proficiency_level' => 'required|string|max:50',
                'years_of_experience' => 'nullable|numeric|min:0',
                'last_used' => 'nullable|date',
                'is_certified' => 'boolean',
                'certification_name' => 'nullable|string|max:255',
                'certification_date' => 'nullable|date',
            ]);

            // Check if skill is already assigned
            $existing = EmployeeSkill::where('employee_id', $employee->id)
                ->where('skill_id', $validated['skill_id'])
                ->first();

            if ($existing) {
                return ApiResponse::error('This skill is already assigned to the employee.', 422);
            }

            $validated['tenant_id'] = $request->user()->tenant_id;
            $validated['employee_id'] = $employee->id;

            $employeeSkill = EmployeeSkill::create($validated);

            // Calculate profile completeness
            $this->profileCompletenessService->calculate($employee);

            return ApiResponse::created($employeeSkill->load('skill'), 'Skill assigned successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Assigning skill');
        }
    }

    /**
     * Update an assigned skill
     */
    public function update(Request $request, Employee $employee, $id)
    {
        try {
            $employeeSkill = EmployeeSkill::where('employee_id', $employee->id)
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            $validated = $request->validate([
                'proficiency_level' => 'sometimes|required|string|max:50',
                'years_of_experience' => 'nullable|numeric|min:0',
                'last_used' => 'nullable|date',
                'is_certified' => 'boolean',
                'certification_name' => 'nullable|string|max:255',
                'certification_date' => 'nullable|date',
            ]);

            $employeeSkill->update($validated);

            // Calculate profile completeness
            $this->profileCompletenessService->calculate($employee);

            return ApiResponse::success($employeeSkill->load('skill'), 'Skill updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Updating assigned skill');
        }
    }

    /**
     * Remove an assigned skill
     */
    public function destroy(Request $request, Employee $employee, $id)
    {
        try {
            $employeeSkill = EmployeeSkill::where('employee_id', $employee->id)
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            $employeeSkill->delete();

            // Calculate profile completeness
            $this->profileCompletenessService->calculate($employee);

            return ApiResponse::success(null, 'Skill unassigned successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Removing assigned skill');
        }
    }
}
