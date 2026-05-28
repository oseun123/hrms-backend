<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantManagementController extends Controller
{
    public function __construct(protected TenantProvisioningService $provisioningService) {}

    /**
     * List all tenants with pagination and optional filters
     */
    public function index(Request $request)
    {
        $query = Tenant::withTrashed()
            ->withCount(['users', 'employees'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('slug', 'like', "%{$request->search}%")
                    ->orWhere('contact_email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('deleted_at')->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->whereNull('deleted_at')->where('is_active', false);
            } elseif ($request->status === 'deleted') {
                $query->whereNotNull('deleted_at');
            }
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        $tenants = $query->paginate($request->get('per_page', 15));

        return ApiResponse::success($tenants);
    }

    /**
     * Show a single tenant with details
     */
    public function show($id)
    {
        $tenant = Tenant::withTrashed()
            ->withCount(['users', 'employees'])
            ->findOrFail($id);

        return ApiResponse::success($tenant);
    }

    /**
     * Create a new tenant and provision default admin user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => 'required|string|max:100|unique:tenants,slug|alpha_dash',
            'contact_email'  => 'nullable|email|max:255',
            'plan'           => 'nullable|in:starter,growth,enterprise',
            'max_users'      => 'nullable|integer|min:1',
            'trial_ends_at'  => 'nullable|date|after:today',
            'notes'          => 'nullable|string|max:1000',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $provisioningResult = $this->provisioningService->provision($request->all());

                $superAdmin = auth('super-admin')->user();
                SuperAdminActivityLog::create([
                    'super_admin_id' => $superAdmin->id,
                    'action'         => 'tenant.created',
                    'description'    => "Tenant '{$provisioningResult['tenant']->name}' created with slug '{$provisioningResult['tenant']->slug}'",
                    'subject_type'   => Tenant::class,
                    'subject_id'     => $provisioningResult['tenant']->id,
                    'meta'           => [
                        'admin_email' => $request->admin_email,
                        'plan'        => $provisioningResult['tenant']->plan,
                    ],
                ]);

                return $provisioningResult;
            });

            return ApiResponse::success($result, 'Tenant created and provisioned successfully', 201);
        } catch (\Exception $e) {
            Log::error('Tenant provisioning failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ApiResponse::error('Failed to create tenant: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Update tenant details
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::withTrashed()->findOrFail($id);

        $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'slug'          => "sometimes|required|string|max:100|alpha_dash|unique:tenants,slug,{$id}",
            'domain'        => "sometimes|nullable|string|max:255|unique:tenants,domain,{$id}",
            'contact_email' => 'sometimes|nullable|email|max:255',
            'plan'          => 'sometimes|nullable|in:starter,growth,enterprise',
            'max_users'     => 'sometimes|nullable|integer|min:1',
            'trial_ends_at' => 'sometimes|nullable|date',
            'notes'         => 'sometimes|nullable|string|max:1000',
        ]);

        $tenant->update($request->only([
            'name',
            'slug',
            'domain',
            'contact_email',
            'plan',
            'max_users',
            'trial_ends_at',
            'notes',
        ]));

        $superAdmin = auth('super-admin')->user();
        SuperAdminActivityLog::create([
            'super_admin_id' => $superAdmin->id,
            'action'         => 'tenant.updated',
            'description'    => "Tenant '{$tenant->name}' updated",
            'subject_type'   => Tenant::class,
            'subject_id'     => $tenant->id,
        ]);

        return ApiResponse::success($tenant->fresh(), 'Tenant updated successfully');
    }

    /**
     * Activate a tenant
     */
    public function activate($id)
    {
        $tenant = Tenant::withTrashed()->findOrFail($id);
        $tenant->update(['is_active' => true]);

        if ($tenant->trashed()) {
            $tenant->restore();
        }

        $superAdmin = auth('super-admin')->user();
        SuperAdminActivityLog::create([
            'super_admin_id' => $superAdmin->id,
            'action'         => 'tenant.activated',
            'description'    => "Tenant '{$tenant->name}' activated",
            'subject_type'   => Tenant::class,
            'subject_id'     => $tenant->id,
        ]);

        return ApiResponse::success($tenant->fresh(), 'Tenant activated successfully');
    }

    /**
     * Deactivate a tenant
     */
    public function deactivate($id)
    {
        $tenant = Tenant::withTrashed()->findOrFail($id);
        $tenant->update(['is_active' => false]);

        $superAdmin = auth('super-admin')->user();
        SuperAdminActivityLog::create([
            'super_admin_id' => $superAdmin->id,
            'action'         => 'tenant.deactivated',
            'description'    => "Tenant '{$tenant->name}' deactivated",
            'subject_type'   => Tenant::class,
            'subject_id'     => $tenant->id,
        ]);

        return ApiResponse::success($tenant->fresh(), 'Tenant deactivated successfully');
    }

    /**
     * Soft-delete a tenant
     */
    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => false]);
        $tenant->delete();

        $superAdmin = auth('super-admin')->user();
        SuperAdminActivityLog::create([
            'super_admin_id' => $superAdmin->id,
            'action'         => 'tenant.deleted',
            'description'    => "Tenant '{$tenant->name}' soft-deleted",
            'subject_type'   => Tenant::class,
            'subject_id'     => $tenant->id,
        ]);

        return ApiResponse::success(null, 'Tenant deleted successfully');
    }

    /**
     * Send welcome email to tenant admin
     */
    public function sendWelcomeEmail($id)
    {
        $tenant = Tenant::findOrFail($id);

        // Find the admin user for this tenant
        $adminUser = $tenant->users()->whereHas('roles', function ($q) {
            $q->where('slug', 'admin');
        })->first();

        if (!$adminUser) {
            return ApiResponse::error('Admin user not found for this tenant', 404);
        }

        // Send to admin user
        $adminUser->notify(new \App\Notifications\TenantWelcomeNotification($tenant, $adminUser));

        // Also send to tenant contact email if it exists and is different from admin email
        if ($tenant->contact_email && $tenant->contact_email !== $adminUser->email) {
            $tenant->notify(new \App\Notifications\TenantWelcomeNotification($tenant, $adminUser));
        }

        $superAdmin = auth('super-admin')->user();
        SuperAdminActivityLog::create([
            'super_admin_id' => $superAdmin->id,
            'action'         => 'tenant.welcome_email_sent',
            'description'    => "Welcome email sent to tenant '{$tenant->name}' admin '{$adminUser->email}'",
            'subject_type'   => Tenant::class,
            'subject_id'     => $tenant->id,
            'meta'           => [
                'admin_email' => $adminUser->email,
            ],
        ]);

        return ApiResponse::success(null, 'Welcome email sent successfully');
    }

    /**
     * List super admin activity logs
     */
    public function activityLogs(Request $request)
    {
        $logs = SuperAdminActivityLog::with('superAdmin')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return ApiResponse::success($logs);
    }
}
