<?php

namespace App\Services;

use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeNumberFormat;
use App\Models\Hris\EmployeeNumberSequence;
use Illuminate\Support\Facades\DB;

class EmployeeNumberService
{
    /**
     * Generate the next employee number for a tenant
     */
    public function generateNextNumber($tenantId): string
    {
        return DB::transaction(function () use ($tenantId) {
            // Get the default active format for this tenant
            $format = EmployeeNumberFormat::default($tenantId)->first();

            if (!$format) {
                // Fallback: create a simple sequential number if no format exists
                $lastEmployee = Employee::where('tenant_id', $tenantId)
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = $lastEmployee ? ((int) filter_var($lastEmployee->employee_number, FILTER_SANITIZE_NUMBER_INT)) + 1 : 1;
                return 'EMP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            // Get or create the sequence tracker
            $sequence = EmployeeNumberSequence::getOrCreateSequence(
                $format->id,
                $tenantId,
                $format->reset_sequence
            );

            // Increment the sequence
            $sequence->increment('last_sequence');
            $sequence->refresh();

            // Generate the employee number
            return $this->buildEmployeeNumber($format, $sequence->last_sequence);
        });
    }

    /**
     * Preview what the next employee number would look like
     */
    public function previewFormat($tenantId, ?array $formatData = null): string
    {
        if ($formatData) {
            // Create a temporary format object for preview
            $format = new EmployeeNumberFormat($formatData);
            return $format->generatePreview(1);
        }

        // Use the default format
        $format = EmployeeNumberFormat::default($tenantId)->first();

        if (!$format) {
            return 'EMP-0001';
        }

        $sequence = EmployeeNumberSequence::getOrCreateSequence(
            $format->id,
            $tenantId,
            $format->reset_sequence
        );

        return $this->buildEmployeeNumber($format, $sequence->last_sequence + 1);
    }

    /**
     * Regenerate all employee numbers based on the current format
     */
    public function regenerateAllNumbers($tenantId): array
    {
        return DB::transaction(function () use ($tenantId) {
            $format = EmployeeNumberFormat::default($tenantId)->first();

            if (!$format) {
                throw new \Exception('No default format found for this tenant');
            }

            // Get all employees ordered by creation date
            $employees = Employee::where('tenant_id', $tenantId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Reset all sequences for this format
            EmployeeNumberSequence::where('format_id', $format->id)->delete();

            $updatedCount = 0;
            foreach ($employees as $employee) {
                // Get or create sequence
                $sequence = EmployeeNumberSequence::getOrCreateSequence(
                    $format->id,
                    $tenantId,
                    $format->reset_sequence
                );

                // Increment sequence
                $sequence->increment('last_sequence');
                $sequence->refresh();

                // Generate and update employee number
                $newNumber = $this->buildEmployeeNumber($format, $sequence->last_sequence);
                $employee->update(['employee_number' => $newNumber]);
                $updatedCount++;
            }

            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'last_sequence' => $sequence->last_sequence ?? 0,
            ];
        });
    }

    /**
     * Build the employee number from format and sequence
     */
    protected function buildEmployeeNumber(EmployeeNumberFormat $format, int $sequence): string
    {
        $parts = [];

        // Add prefix
        if ($format->prefix) {
            $parts[] = $format->prefix;
        }

        // Add year
        if ($format->include_year) {
            $year = now()->format($format->year_format === 'YYYY' ? 'Y' : 'y');
            $parts[] = $year;
        }

        // Add month
        if ($format->include_month) {
            $month = now()->format($format->month_format === 'MM' ? 'm' : 'n');
            $parts[] = $month;
        }

        // Add sequence
        $parts[] = str_pad($sequence, $format->sequence_length, '0', STR_PAD_LEFT);

        return implode($format->separator, $parts);
    }
}
