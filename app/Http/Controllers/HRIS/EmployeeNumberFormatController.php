<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\EmployeeNumberFormat;
use App\Services\EmployeeNumberService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeNumberFormatController extends Controller
{
    use HandlesApiErrors;

    protected $employeeNumberService;

    public function __construct(EmployeeNumberService $employeeNumberService)
    {
        $this->employeeNumberService = $employeeNumberService;
    }

    /**
     * Get the current employee number format for the tenant
     */
    public function show(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $format = EmployeeNumberFormat::default($tenantId)->first();

            if (!$format) {
                return ApiResponse::success([
                    'exists' => false,
                    'format' => null,
                    'preview' => 'EMP-0001',
                ], 'No format configured');
            }

            $preview = $this->employeeNumberService->previewFormat($tenantId);

            return ApiResponse::success([
                'exists' => true,
                'format' => $format,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching employee number format');
        }
    }

    /**
     * Update the employee number format
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'format_name' => 'required|string|max:255',
                'prefix' => 'nullable|string|max:50',
                'include_year' => 'required|boolean',
                'year_format' => 'required_if:include_year,true|nullable|in:YYYY,YY',
                'include_month' => 'required|boolean',
                'month_format' => 'required_if:include_month,true|nullable|in:MM,M',
                'separator' => 'required|string|max:10',
                'sequence_length' => 'required|integer|min:1|max:10',
                'reset_sequence' => 'required|in:never,yearly,monthly',
            ]);

            if (empty($validated['year_format'])) {
                $validated['year_format'] = 'YYYY';
            }

            $tenantId = $request->user()->tenant_id;

            return DB::transaction(function () use ($validated, $tenantId, $request) {
                // Deactivate all existing formats for this tenant
                EmployeeNumberFormat::where('tenant_id', $tenantId)
                    ->update(['is_default' => false]);

                // Create or update the format
                $format = EmployeeNumberFormat::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'is_active' => true,
                    ],
                    array_merge($validated, [
                        'is_default' => true,
                        'created_by' => Auth::id(),
                        'sample_format' => $this->generateSampleFormat($validated),
                    ])
                );

                $preview = $this->employeeNumberService->previewFormat($tenantId);

                return ApiResponse::success([
                    'format' => $format->fresh(),
                    'preview' => $preview,
                ], 'Format configuration updated successfully');
            });
        } catch (\Exception $e) {
            return $this->handleException($e, 'Updating employee number format');
        }
    }

    /**
     * Preview a format configuration without saving
     */
    public function preview(Request $request)
    {
        try {
            $validated = $request->validate([
                'prefix' => 'nullable|string|max:50',
                'include_year' => 'required|boolean',
                'year_format' => 'required_if:include_year,true|nullable|in:YYYY,YY',
                'include_month' => 'required|boolean',
                'month_format' => 'required_if:include_month,true|nullable|in:MM,M',
                'separator' => 'required|string|max:10',
                'sequence_length' => 'required|integer|min:1|max:10',
            ]);

            if (empty($validated['year_format'])) {
                $validated['year_format'] = 'YYYY';
            }

            $tenantId = $request->user()->tenant_id;
            $preview = $this->employeeNumberService->previewFormat($tenantId, $validated);

            return ApiResponse::success([
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Previewing format');
        }
    }

    /**
     * Regenerate all employee numbers based on current format
     */
    public function regenerate(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $result = $this->employeeNumberService->regenerateAllNumbers($tenantId);

            return ApiResponse::success([
                'summary' => $result,
            ], 'All employee numbers have been regenerated');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Regenerating employee numbers');
        }
    }

    /**
     * Generate a sample format string for display
     */
    protected function generateSampleFormat(array $data): string
    {
        $parts = [];

        if (!empty($data['prefix'])) {
            $parts[] = $data['prefix'];
        }

        if ($data['include_year']) {
            $parts[] = $data['year_format'];
        }

        if ($data['include_month']) {
            $parts[] = $data['month_format'];
        }

        $parts[] = str_repeat('0', $data['sequence_length']);

        return implode($data['separator'], $parts);
    }
}
