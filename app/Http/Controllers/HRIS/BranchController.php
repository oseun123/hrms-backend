<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Branch;
use App\Traits\HandlesApiErrors;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    use HandlesApiErrors;

    public function index(Request $request)
    {
        try {
            $query = Branch::with(['contactPersons'])
                ->withCount('employees')
                ->where('tenant_id', $request->user()->tenant_id);

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            }

            if ($request->hasAny(['per_page', 'page'])) {
                $branches = $query->paginate($request->get('per_page', 15));
            } else {
                $branches = $query->get();
            }

            return ApiResponse::success($branches);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching branches');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:branches,code,NULL,id,tenant_id,' . $tenantId,
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'is_active' => 'boolean',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'contact_person_ids' => 'nullable|array',
                'contact_person_ids.*' => 'exists:employees,id,tenant_id,' . $tenantId,
            ]);

            $validated['tenant_id'] = $tenantId;
            $validated['created_by'] = auth()->id();

            $branch = Branch::create($validated);

            if ($request->has('contact_person_ids')) {
                $branch->contactPersons()->sync($request->contact_person_ids);
            }

            return ApiResponse::created(
                $branch->load('contactPersons'),
                'Branch created successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Branch creation');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $branch = Branch::with(['contactPersons'])
                ->withCount('employees')
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Fetch employees in this branch
            $employees = \App\Models\Hris\Employee::whereHas('employmentDetails', function ($query) use ($id) {
                $query->where('branch_id', $id);
            })->with(['user', 'employmentDetails.position', 'employmentDetails.department'])->get();

            $branch->setRelation('employees', $employees);

            return ApiResponse::success($branch);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Branch not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching branch');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $branch = Branch::where('tenant_id', $tenantId)
                ->findOrFail($id);

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:branches,code,' . $id . ',id,tenant_id,' . $tenantId,
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'is_active' => 'boolean',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'contact_person_ids' => 'nullable|array',
                'contact_person_ids.*' => 'exists:employees,id,tenant_id,' . $tenantId,
            ]);

            $validated['updated_by'] = auth()->id();

            if ($branch->is_default) {
                if (isset($validated['name']) && $validated['name'] !== $branch->name) {
                    return ApiResponse::error('The name of the default headquarters branch cannot be modified.', 422);
                }
                if (isset($validated['code']) && $validated['code'] !== $branch->code) {
                    return ApiResponse::error('The code of the default headquarters branch cannot be modified.', 422);
                }
                if (isset($validated['is_active']) && !$validated['is_active']) {
                    return ApiResponse::error('The default headquarters branch must remain active.', 422);
                }
            }

            $branch->update($validated);

            if ($request->has('contact_person_ids')) {
                $branch->contactPersons()->sync($request->contact_person_ids);
            }

            return ApiResponse::success(
                $branch->load('contactPersons'),
                'Branch updated successfully'
            );
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Branch not found');
        } catch (ValidationException $e) {
            return ApiResponse::validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Branch update');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $branch = Branch::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            if ($branch->is_default) {
                return ApiResponse::error('The default headquarters branch cannot be deleted.', 422);
            }

            // Check if branch has employees assigned
            if ($branch->employees()->count() > 0) {
                return ApiResponse::error('Cannot delete branch with assigned employees', 422);
            }

            $branch->delete();

            return ApiResponse::success(null, 'Branch deleted successfully');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('Branch not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Branch deletion');
        }
    }
}
