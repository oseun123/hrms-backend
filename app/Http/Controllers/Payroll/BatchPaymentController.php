<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\AnnualSalaryStructure;
use App\Models\Payroll\BatchPayment;
use App\Models\Payroll\MonthlyPayment;
use App\Models\Payroll\MonthlyPaymentItem;
use App\Models\Payroll\LeaveAllowanceRequest;
use App\Models\Payroll\PayGroup;
use App\Services\Payroll\MonthlyPaymentRecalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BatchPaymentController extends Controller
{
    protected $recalculator;

    public function __construct(MonthlyPaymentRecalculator $recalculator)
    {
        $this->recalculator = $recalculator;
    }

    /**
     * Display a listing of batch payments.
     */
    public function index(Request $request)
    {
        $batches = BatchPayment::with('payGroup')
            ->withCount('monthlyPayments')
            ->when($request->month, fn($q) => $q->where('month', $request->month))
            ->when($request->year, fn($q) => $q->where('year', $request->year))
            ->when($request->pay_group_id, fn($q) => $q->where('pay_group_id', $request->pay_group_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $batches
        ]);
    }

    /**
     * Generate a monthly batch payment from annual templates.
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_group_id' => 'required',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $results = [
            'generated' => 0,
            'errors' => []
        ];

        if ($request->pay_group_id === 'all') {
            \Illuminate\Support\Facades\Log::info("DIAGNOSTIC: \ud83d\udce6 Batch generate called with pay_group_id='all', will loop through all active groups");
            $payGroups = PayGroup::active()->get();
            foreach ($payGroups as $payGroup) {
                try {
                    $this->processGeneration($payGroup->id, $request->month, $request->year);
                    $results['generated']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Group {$payGroup->name}: " . $e->getMessage();
                }
            }
        } else {
            try {
                $this->processGeneration($request->pay_group_id, $request->month, $request->year);
                $results['generated']++;
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Process completed: {$results['generated']} batches generated.",
            'data' => $results
        ], 201);
    }

    protected function processGeneration($payGroupId, $month, $year)
    {
        \Illuminate\Support\Facades\Log::info("DIAGNOSTIC: Starting generation for Group $payGroupId, Period $month/$year");
        // 1. Fetch active annual structures for group
        $allStructures = AnnualSalaryStructure::with(['items', 'employee.employmentDetails'])
            ->where('pay_group_id', $payGroupId)
            ->where('status', 'active')
            ->get();

        \Illuminate\Support\Facades\Log::info("DIAGNOSTIC: Active structures found in DB: " . $allStructures->count());
        foreach ($allStructures as $s) {
            // FIXED: Use the payGroups() relationship instead of pay_group_id column
            $empPayGroups = $s->employee->payGroups->pluck('id')->toArray();
            $empBelongsToThisGroup = in_array($payGroupId, $empPayGroups);
            $groupDisplay = empty($empPayGroups) ? 'NONE' : implode(',', $empPayGroups);
            $mismatch = !$empBelongsToThisGroup ? ' ⚠️ MISMATCH' : '';
            \Illuminate\Support\Facades\Log::info("DIAGNOSTIC:  - Struct ID: {$s->id}, Emp ID: {$s->employee_id}, EmpName: {$s->employee->full_name}, EmpGroups: [{$groupDisplay}], StructGroup: {$s->pay_group_id}{$mismatch}");
        }

        if ($allStructures->isEmpty()) {
            throw new \Exception('No active annual salary structures found for this group. Generate structures first.');
        }

        // 2. Identify employees in this group who have ALREADY been paid for this period (across any batch)
        $paidEmployeeIds = MonthlyPayment::whereHas('batchPayment', function ($q) use ($month, $year) {
            $q->where('month', $month)->where('year', $year);
        })->pluck('employee_id')->toArray();

        \Illuminate\Support\Facades\Log::info("DIAGNOSTIC: Already paid employee IDs: [" . implode(',', $paidEmployeeIds) . "]");

        // Enhanced diagnostic: Show which batch each paid employee belongs to
        if (!empty($paidEmployeeIds)) {
            $paidDetails = MonthlyPayment::whereIn('employee_id', $paidEmployeeIds)
                ->whereHas('batchPayment', function ($q) use ($month, $year) {
                    $q->where('month', $month)->where('year', $year);
                })
                ->with(['employee', 'batchPayment.payGroup'])
                ->get();
            foreach ($paidDetails as $pd) {
                \Illuminate\Support\Facades\Log::info("DIAGNOSTIC:    ↳ Emp {$pd->employee_id} ({$pd->employee->full_name}) paid in Batch '{$pd->batchPayment->batch_name}' for Group '{$pd->batchPayment->payGroup->name}' (ID: {$pd->batchPayment->pay_group_id})");
            }
        }

        // 3. Filter for truly "unpaid" employees
        $unpaidStructures = $allStructures->filter(function ($s) use ($paidEmployeeIds) {
            return !in_array($s->employee_id, $paidEmployeeIds);
        });

        \Illuminate\Support\Facades\Log::info("DIAGNOSTIC: Unpaid structures to process: " . $unpaidStructures->count());

        if ($unpaidStructures->isEmpty()) {
            throw new \Exception('All active employees in this group have already been processed for this period.');
        }

        // 4. Check for existing DRAFT batch for this specific group/period
        // This prevents creating multiple identical draft batches while one is still being reviewed.
        $existingDraft = BatchPayment::where([
            'pay_group_id' => $payGroupId,
            'month'        => $month,
            'year'         => $year,
            'status'       => 'draft'
        ])->first();

        if ($existingDraft) {
            throw new \Exception('A DRAFT batch already exists for this group and period. Please edit or delete it before generating a new one.');
        }

        try {
            DB::beginTransaction();

            $batchCountForPeriod = BatchPayment::where(['month' => $month, 'year' => $year, 'pay_group_id' => $payGroupId])->count();
            $batchName = $batchCountForPeriod > 0 ? "Supplementary Batch #" . ($batchCountForPeriod + 1) : "Main Batch";

            $batch = BatchPayment::create([
                'tenant_id' => auth('sanctum')->user()->tenant_id,
                'pay_group_id' => $payGroupId,
                'month' => $month,
                'year' => $year,
                'batch_name' => $batchName,
                'status' => 'draft'
            ]);

            foreach ($unpaidStructures as $structure) {
                $employee = $structure->employee;
                $includedItems = [];
                $monthlyGross = 0;
                $monthlyRelief = 0;
                $monthlyOtherDeductions = 0;

                foreach ($structure->items as $item) {
                    $amount = 0;
                    $isIncluded = false;

                    if (($item->frequency ?? 'monthly') === 'monthly') {
                        $amount = round($item->annual_amount / 12, 2);
                        $isIncluded = true;
                    } else {
                        // Skip Leave Allowance components in the main loop as they are handled 
                        // by the separate on-request integration block below.
                        $component = $item->component;
                        if ($component && (stripos($component->name, 'leave allowance') !== false || stripos($component->code, 'LEAVE_ALLOW') !== false)) {
                            continue;
                        }

                        // Annual/Scheduled logic
                        $targetMonth = null;
                        if ($item->payment_month === 'anniversary') {
                            $targetMonth = $employee->employmentDetails?->hire_date ? (int)date('m', strtotime($employee->employmentDetails->hire_date)) : null;
                        } elseif ($item->payment_month === 'birthday') {
                            $targetMonth = $employee->date_of_birth ? (int)date('m', strtotime($employee->date_of_birth)) : null;
                        } else {
                            $targetMonth = (int)$item->payment_month;
                        }

                        if ($targetMonth === (int)$month) {
                            $amount = round($item->annual_amount, 2);
                            $isIncluded = true;
                        }
                    }

                    if ($isIncluded) {
                        $isOneTime = ($item->frequency ?? 'monthly') !== 'monthly';
                        $includedItems[] = [
                            'component_id' => $item->component_id,
                            'amount' => $amount,
                            'is_one_time' => $isOneTime,
                        ];

                        $component = \App\Models\Payroll\SalaryComponent::find($item->component_id);
                        if ($component->type === 'earning') {
                            $monthlyGross += $amount;
                        } else {
                            $monthlyOtherDeductions += $amount;
                        }

                        if ($component->is_tax_deductible) {
                            $monthlyRelief += $amount;
                        }
                    }
                }

                $monthly = MonthlyPayment::create([
                    'batch_payment_id' => $batch->id,
                    'employee_id' => $structure->employee_id,
                    'gross_salary' => 0,
                    'net_salary' => 0,
                    'tax_amount' => 0,
                    'total_relief' => 0,
                    'pension_ee' => 0,
                    'pension_er' => 0,
                ]);

                foreach ($includedItems as $itemInfo) {
                    MonthlyPaymentItem::create([
                        'monthly_payment_id' => $monthly->id,
                        'component_id' => $itemInfo['component_id'],
                        'amount' => $itemInfo['amount'],
                        'is_one_time' => $itemInfo['is_one_time']
                    ]);
                }

                // --- INTEGRATION: Include Approved Leave Allowances ---
                // Logic: 
                // 1. Check if the pay group is using leave allowance (via its wage items)
                // 2. Check if the employee has an approved leave allowance for current or past months

                $payGroupEnabled = $structure->payGroup->wageItems()
                    ->where('has_leave_allowance', true)
                    ->exists();

                if ($payGroupEnabled) {
                    $dueAllowances = LeaveAllowanceRequest::where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->whereNull('batch_payment_id')
                        ->whereHas('leaveRequest', function ($q) use ($month, $year) {
                            $q->where(function ($sub) use ($month, $year) {
                                // Due this month or in the past
                                $sub->whereYear('start_date', '<', $year)
                                    ->orWhere(function ($s) use ($month, $year) {
                                        $s->whereYear('start_date', $year)
                                            ->whereMonth('start_date', '<=', $month);
                                    });
                            });
                        })
                        ->get();

                    foreach ($dueAllowances as $allowance) {
                        MonthlyPaymentItem::create([
                            'monthly_payment_id' => $monthly->id,
                            'component_id' => $allowance->annual_structure_item_id ?
                                \App\Models\Payroll\AnnualSalaryStructureItem::find($allowance->annual_structure_item_id)->component_id :
                                null,
                            'amount' => $allowance->amount,
                            'is_one_time' => true,
                            'reason' => "Annual Leave Allowance ({$allowance->leave_year})"
                        ]);

                        // Link the allowance to this batch and payment
                        // Status remains 'approved' until the batch is authorized
                        $allowance->update([
                            'batch_payment_id' => $batch->id,
                            'monthly_payment_id' => $monthly->id,
                        ]);
                    }
                }
                // ---------------------------------------------------

                // Accurate statutory recalculation for the month
                $this->recalculator->recalculateFull($monthly);
            }

            DB::commit();
            return $batch;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Show batch details with individual payments.
     */
    public function show($id)
    {
        $batch = BatchPayment::with(['payGroup', 'monthlyPayments.employee', 'monthlyPayments.items.component'])->find($id);

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $batch
        ]);
    }

    /**
     * Delete a draft batch.
     */
    public function destroy($id)
    {
        $batch = BatchPayment::find($id);

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found'
            ], 404);
        }

        if ($batch->status === 'authorized') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an authorized batch'
            ], 400);
        }

        // Recovery logic: Unlink any leave allowances tied to this draft batch
        LeaveAllowanceRequest::where('batch_payment_id', $batch->id)->update([
            'batch_payment_id' => null,
            'monthly_payment_id' => null,
        ]);

        $batch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Batch deleted successfully'
        ]);
    }

    /**
     * Bulk delete draft batches.
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:payroll_batch_payments,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $ids = $request->ids;
        $batches = BatchPayment::whereIn('id', $ids)->get();
        $processed = 0;
        $failed = 0;

        foreach ($batches as $batch) {
            // Safety: Only draft batches can be deleted
            if ($batch->status === 'authorized') {
                $failed++;
                continue;
            }

            // Recovery logic: Unlink any leave allowances
            LeaveAllowanceRequest::where('batch_payment_id', $batch->id)->update([
                'batch_payment_id' => null,
                'monthly_payment_id' => null,
            ]);

            $batch->delete();
            $processed++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted $processed batch(es)." . ($failed > 0 ? " Skipped $failed authorized batch(es)." : ""),
            'data' => [
                'deleted_count' => $processed,
                'skipped_count' => $failed
            ]
        ]);
    }

    /**
     * Authorize a batch payment.
     * Once authorized, no more changes are allowed.
     */
    public function authorizeBatch($id)
    {
        $batch = BatchPayment::find($id);

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found'
            ], 404);
        }

        if ($batch->status === 'authorized') {
            return response()->json([
                'success' => false,
                'message' => 'Batch is already authorized'
            ], 400);
        }

        $batch->update([
            'status' => 'authorized',
            'authorized_at' => now(),
            'authorized_by' => auth('sanctum')->id()
        ]);

        // Activation logic: Mark all linked leave allowances as 'paid'
        LeaveAllowanceRequest::where('batch_payment_id', $batch->id)->update([
            'status' => 'paid'
        ]);

        // Send notifications to all employees in the batch
        foreach ($batch->monthlyPayments as $payment) {
            $payment->employee->user->notify(new \App\Notifications\PayrollAuthorized($payment));
            $payment->update(['is_payslip_sent' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Batch authorized successfully. Payments are now locked and notifications sent.'
        ]);
    }

    /**
     * Add an on-the-fly component to a monthly payment.
     */
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'monthly_payment_id' => 'required|exists:payroll_monthly_payments,id',
            'component' => 'required|array',
            'component.name' => 'required|string|min:3',
            'component.type' => 'required|in:earning,deduction',
            'component.is_taxable' => 'required|boolean',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $payment = MonthlyPayment::with(['batchPayment', 'items.component'])->find($request->monthly_payment_id);

        if ($payment->batchPayment->status === 'authorized') {
            return response()->json(['success' => false, 'message' => 'Cannot modify payments in an authorized batch'], 400);
        }

        // Use existing or create new ad-hoc component
        $code = strtoupper(str_replace(' ', '_', $request->component['name']));
        $component = \App\Models\Payroll\SalaryComponent::updateOrCreate(
            [
                'tenant_id' => auth('sanctum')->user()->tenant_id,
                'code' => $code
            ],
            [
                'name' => $request->component['name'],
                'type' => $request->component['type'],
                'category' => 'variable',
                'is_taxable' => $request->component['is_taxable'],
                'is_tax_deductible' => false,
                'calculation_type' => 'fixed',
                'amount_value' => 0,
                'is_active' => true,
                'is_system_defined' => false,
                'show_on_payslip' => true,
            ]
        );

        try {
            $recalculator = new \App\Services\Payroll\MonthlyPaymentRecalculator();
            $updatedPayment = $recalculator->addItemAndRecalculate(
                $payment,
                $component,
                $request->amount,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Item added and payment recalculated',
                'data' => [
                    'payment' => $updatedPayment,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Bulk add an item to multiple employees in a batch.
     */
    public function bulkAddItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|exists:payroll_batch_payments,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
            'component' => 'required|array',
            'component.name' => 'required|string|min:3',
            'component.type' => 'required|in:earning,deduction',
            'component.is_taxable' => 'required|boolean',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $batch = BatchPayment::find($request->batch_id);

        if ($batch->status === 'authorized') {
            return response()->json(['success' => false, 'message' => 'Cannot modify an authorized batch'], 400);
        }

        // Use existing or create new ad-hoc component
        $code = strtoupper(str_replace(' ', '_', $request->component['name']));
        $component = \App\Models\Payroll\SalaryComponent::updateOrCreate(
            [
                'tenant_id' => auth('sanctum')->user()->tenant_id,
                'code' => $code
            ],
            [
                'name' => $request->component['name'],
                'type' => $request->component['type'],
                'category' => 'variable',
                'is_taxable' => $request->component['is_taxable'],
                'is_tax_deductible' => false,
                'calculation_type' => 'fixed',
                'amount_value' => 0,
                'is_active' => true,
                'is_system_defined' => false,
                'show_on_payslip' => true,
            ]
        );

        $recalculator = new \App\Services\Payroll\MonthlyPaymentRecalculator();
        $processed = [];
        $failed = [];

        foreach ($request->employee_ids as $employeeId) {
            try {
                $payment = MonthlyPayment::with(['employee', 'batchPayment'])
                    ->where('batch_payment_id', $request->batch_id)
                    ->where('employee_id', $employeeId)
                    ->first();

                if (!$payment) {
                    $failed[] = [
                        'employee_id' => $employeeId,
                        'reason' => 'Employee not found in this batch'
                    ];
                    continue;
                }

                $updatedPayment = $recalculator->addItemAndRecalculate(
                    $payment,
                    $component,
                    $request->amount,
                    $request->reason
                );

                $processed[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $payment->employee->first_name . ' ' . $payment->employee->last_name,
                    'new_net' => $updatedPayment->net_salary,
                ];
            } catch (\Exception $e) {
                $failed[] = [
                    'employee_id' => $employeeId,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Item added to " . count($processed) . " employee(s)",
            'data' => [
                'processed' => count($processed),
                'failed' => count($failed),
                'items_created' => $processed,
                'errors' => $failed,
            ]
        ]);
    }

    /**
     * Remove an on-the-fly item and recalculate.
     */
    public function removeItem($itemId)
    {
        $item = MonthlyPaymentItem::with('monthlyPayment.batchPayment')->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        if ($item->monthlyPayment->batchPayment->status === 'authorized') {
            return response()->json(['success' => false, 'message' => 'Cannot modify an authorized batch'], 400);
        }

        $payment = $item->monthlyPayment;
        $item->delete();

        try {
            $recalculator = new \App\Services\Payroll\MonthlyPaymentRecalculator();
            $updatedPayment = $recalculator->recalculateFull($payment);

            return response()->json([
                'success' => true,
                'message' => 'Item removed and payment recalculated',
                'data' => $updatedPayment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an on-the-fly item and recalculate.
     */
    public function updateItem(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $item = MonthlyPaymentItem::with('monthlyPayment.batchPayment')->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        if ($item->monthlyPayment->batchPayment->status === 'authorized') {
            return response()->json(['success' => false, 'message' => 'Cannot modify an authorized batch'], 400);
        }

        $item->update([
            'amount' => $request->amount,
            'reason' => $request->reason,
            'added_at' => now(), // Update timestamp
        ]);

        try {
            $recalculator = new \App\Services\Payroll\MonthlyPaymentRecalculator();
            $updatedPayment = $recalculator->recalculateFull($item->monthlyPayment);

            return response()->json([
                'success' => true,
                'message' => 'Item updated and payment recalculated',
                'data' => $updatedPayment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
