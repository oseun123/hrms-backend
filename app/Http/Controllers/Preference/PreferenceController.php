<?php

namespace App\Http\Controllers\Preference;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Preference\Preference;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreferenceController extends Controller
{
    use HandlesApiErrors;

    /**
     * Search for employees that can be added as HR admins
     */
    public function searchAvailableAdmins(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            $search = $request->query('search');

            // Get existing HR admin IDs from preferences
            $existingAdminIds = Preference::where('tenant_id', $tenantId)
                ->where('category', 'hr_admins')
                ->pluck('key')
                ->toArray();

            $query = Employee::with('user')
                ->where('tenant_id', $tenantId)
                ->whereNotIn('id', $existingAdminIds);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('email', 'like', "%{$search}%");
                        });
                });
            }

            $employees = $query->limit(10)->get()->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->first_name . ' ' . $emp->last_name,
                    'email' => $emp->user ? $emp->user->email : null,
                ];
            });

            return ApiResponse::success($employees);
        } catch (\Exception $e) {
            return $this->handleException($e, 'searching available admins');
        }
    }

    /**
     * Get all preferences for the current user and tenant
     * Merges tenant-wide and user-specific preferences (user overrides tenant)
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Get tenant-wide preferences
            $tenantPrefs = Preference::where('tenant_id', $tenantId)
                ->whereNull('user_id')
                ->get()
                ->keyBy(function ($item) {
                    return $item->category . '.' . $item->key;
                });

            // Get user-specific preferences
            $userPrefs = Preference::where('user_id', $user->id)
                ->get()
                ->keyBy(function ($item) {
                    return $item->category . '.' . $item->key;
                });

            // Merge (user preferences override tenant preferences)
            $merged = $tenantPrefs->merge($userPrefs)->values();

            return ApiResponse::success($merged);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching preferences');
        }
    }

    /**
     * Sync (update or create) multiple preferences
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:tenant,user',
            'preferences' => 'required|array',
            'preferences.*.category' => 'required|string',
            'preferences.*.key' => 'required|string',
            'preferences.*.value' => 'nullable',
        ]);

        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            $scope = $validated['scope'];

            // Authorization check based on category permissions
            if ($scope === 'tenant') {
                $categoryPermissions = [
                    'language' => 'preferences.language_region',
                    'organization' => 'preferences.org_settings',
                    'privacy' => 'preferences.privacy_security',
                    'display' => 'preferences.display',
                    'security_policy' => 'preferences.privacy_security',
                ];

                foreach ($validated['preferences'] as $pref) {
                    $category = $pref['category'];
                    if (isset($categoryPermissions[$category])) {
                        $requiredPermission = $categoryPermissions[$category];
                        if (!$user->hasPermission($requiredPermission)) {
                            return ApiResponse::forbidden("You do not have permission to modify {$category} settings.");
                        }
                    }
                }
            }

            DB::beginTransaction();

            $synced = [];
            foreach ($validated['preferences'] as $pref) {
                $data = [
                    'category' => $pref['category'],
                    'key' => $pref['key'],
                    'value' => $pref['value'] ?? null,
                ];

                if ($scope === 'tenant') {
                    $data['tenant_id'] = $tenantId;
                    $data['user_id'] = null;
                } else {
                    $data['tenant_id'] = $tenantId;
                    $data['user_id'] = $user->id;
                }

                $preference = Preference::updateOrCreate(
                    [
                        'tenant_id' => $data['tenant_id'],
                        'user_id' => $data['user_id'],
                        'category' => $data['category'],
                        'key' => $data['key'],
                    ],
                    ['value' => $data['value']]
                );

                // If syncing at tenant level, clear any individual user overrides for these specific keys
                // to ensure the setting truly "affects all employees" as requested.
                if ($scope === 'tenant') {
                    Preference::where('tenant_id', $tenantId)
                        ->whereNotNull('user_id')
                        ->where('category', $data['category'])
                        ->where('key', $data['key'])
                        ->delete();
                }

                $synced[] = $preference;
            }

            DB::commit();

            return ApiResponse::success($synced, 'Preferences synced successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'syncing preferences');
        }
    }

    /**
     * Get preferences by category
     */
    public function getByCategory(Request $request, $category)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Get tenant-wide preferences for this category
            $tenantPrefs = Preference::where('tenant_id', $tenantId)
                ->whereNull('user_id')
                ->where('category', $category)
                ->get()
                ->keyBy('key');

            // Get user-specific preferences for this category
            $userPrefs = Preference::where('user_id', $user->id)
                ->where('category', $category)
                ->get()
                ->keyBy('key');

            // Merge (user preferences override tenant preferences)
            $merged = $tenantPrefs->merge($userPrefs)->values();

            return ApiResponse::success($merged);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching preferences by category');
        }
    }

    /**
     * Delete a preference
     */
    public function destroy(Request $request, $category, $key)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            $scope = $request->query('scope', 'user');

            $query = Preference::where('tenant_id', $tenantId)
                ->where('category', $category)
                ->where('key', $key);

            if ($scope === 'tenant') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $user->id);
            }

            $preference = $query->first();

            if (! $preference) {
                return ApiResponse::notFound('Preference not found');
            }

            $preference->delete();

            return ApiResponse::success(null, 'Preference deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting preference');
        }
    }
}
