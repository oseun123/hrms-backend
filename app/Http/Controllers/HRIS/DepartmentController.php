<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Traits\HandlesApiErrors;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    use HandlesApiErrors;

    public function index(Request $request)
    {
        try {
            $query = Department::with(['parent', 'manager', 'children'])
                ->where('tenant_id', $request->user()->tenant_id);

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $departments = $query->get();

            return ApiResponse::success($departments);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching departments');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'parent_id' => 'nullable|exists:departments,id',
                'code' => 'required|string|max:50|unique:departments,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'manager_id' => 'nullable|exists:employees,id',
                'cost_center' => 'nullable|string|max:100',
                'location' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'is_active' => 'boolean',
            ]);

            $validated['tenant_id'] = $request->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $department = Department::create($validated);

            return ApiResponse::created(
                $department->load(['parent', 'manager']),
                'Department created successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Department creation');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $department = Department::with(['parent', 'manager', 'children', 'employees', 'positions'])
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            return ApiResponse::success($department);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Department not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching department');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $department = Department::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            $validated = $request->validate([
                'parent_id' => 'nullable|exists:departments,id',
                'code' => 'required|string|max:50|unique:departments,code,' . $id,
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'manager_id' => 'nullable|exists:employees,id',
                'cost_center' => 'nullable|string|max:100',
                'location' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'is_active' => 'boolean',
            ]);

            $validated['updated_by'] = auth()->id();

            $department->update($validated);

            return ApiResponse::success(
                $department->load(['parent', 'manager']),
                'Department updated successfully'
            );
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Department not found');
        } catch (ValidationException $e) {
            return ApiResponse::validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Department update');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $department = Department::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Check if department has children
            if ($department->children()->count() > 0) {
                return ApiResponse::error('Cannot delete department with sub-departments', 422);
            }

            // Check if department has employees
            if ($department->employees()->count() > 0) {
                return ApiResponse::error('Cannot delete department with employees', 422);
            }

            $department->delete();

            return ApiResponse::success(null, 'Department deleted successfully');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Department not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Department deletion');
        }
    }
}
