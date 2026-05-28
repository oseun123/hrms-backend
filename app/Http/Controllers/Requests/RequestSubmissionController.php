<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests\RequestSubmission;
use App\Models\Requests\RequestTemplate;
use App\Models\Hris\Employee;
use App\Services\RequestService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RequestSubmissionController extends Controller
{
    protected $requestService;
    protected $fileUploadService;

    public function __construct(RequestService $requestService, FileUploadService $fileUploadService)
    {
        $this->requestService = $requestService;
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee record not found.'
            ], 404);
        }

        $query = RequestSubmission::where('tenant_id', $user->tenant_id)
            ->where('employee_id', $employee->id)
            ->with(['template', 'employee', 'approvals.approver']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('reference_number', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('submitted_at', 'desc')->paginate($request->get('per_page', 10))
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:request_templates,id',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee record not found.'
            ], 404);
        }

        $template = RequestTemplate::where('tenant_id', $user->tenant_id)->findOrFail($request->template_id);

        if (!$template->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'This template is currently inactive.'
            ], 400);
        }

        // Parse form_data — may arrive as JSON string (multipart) or array (JSON body)
        $formData = $request->form_data;
        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?? [];
        }
        $formData = $formData ?? [];

        // ── Upload any attached files ──────────────────────────────────────────
        $attachments = [];

        // 1. Top-level file inputs (for custom template file fields)
        foreach ($request->allFiles() as $fieldKey => $file) {
            if ($fieldKey === 'files' || $fieldKey === 'expense_files') continue;
            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    $uploaded = $this->fileUploadService->upload($singleFile, 'requests/attachments');
                    $attachments[] = [
                        'name' => $uploaded['metadata']['original_name'],
                        'url'  => $uploaded['url'],
                        'type' => $uploaded['metadata']['mime_type'],
                        'size' => $uploaded['metadata']['size'],
                    ];
                }
            } else {
                $uploaded = $this->fileUploadService->upload($file, 'requests/attachments');
                $attachments[] = [
                    'name' => $uploaded['metadata']['original_name'],
                    'url'  => $uploaded['url'],
                    'type' => $uploaded['metadata']['mime_type'],
                    'size' => $uploaded['metadata']['size'],
                ];
            }
        }

        // 2. Expense form receipts (sent as expense_files[{row_key}])
        if ($request->hasFile('expense_files')) {
            foreach ($request->file('expense_files') as $rowKey => $file) {
                $uploaded = $this->fileUploadService->upload($file, 'requests/attachments');
                $attachments[] = [
                    'name'  => 'Receipt - Row ' . ($rowKey + 1) . ': ' . $uploaded['metadata']['original_name'],
                    'url'   => $uploaded['url'],
                    'type'  => $uploaded['metadata']['mime_type'],
                    'size'  => $uploaded['metadata']['size'],
                ];
            }
        }

        if (!empty($attachments)) {
            $formData['_attachments'] = $attachments;
        }
        // ──────────────────────────────────────────────────────────────────────

        return DB::transaction(function () use ($request, $user, $employee, $template, $formData) {
            $submission = RequestSubmission::create([
                'tenant_id'        => $user->tenant_id,
                'template_id'      => $template->id,
                'employee_id'      => $employee->id,
                'reference_number' => $this->requestService->generateReferenceNumber($user->tenant_id),
                'form_data'        => $formData,
                'status'           => 'pending',
                'submitted_at'     => now(),
            ]);

            $this->requestService->startApprovalChain($submission);

            return response()->json([
                'status'  => 'success',
                'message' => 'Request submitted successfully',
                'data'    => $submission->load(['template', 'approvals.approver'])
            ], 201);
        });
    }

    public function show($id)
    {
        $user = Auth::user();
        $submission = RequestSubmission::where('tenant_id', $user->tenant_id)
            ->with(['template', 'employee', 'approvals.approver'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $submission
        ]);
    }

    public function cancel($id)
    {
        $user = Auth::user();
        $submission = RequestSubmission::where('tenant_id', $user->tenant_id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->findOrFail($id);

        // Rule: Cannot cancel if any level has already approved
        if ($submission->approvals()->where('status', 'approved')->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This request cannot be cancelled because approvals have already begun.'
            ], 403);
        }

        return DB::transaction(function () use ($submission) {
            $submission->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            // Also cancel any pending approvals for this submission
            $submission->approvals()->where('status', 'pending')->update([
                'status' => 'cancelled',
                'actioned_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Request cancelled successfully'
            ]);
        });
    }

    public function download($id)
    {
        $user = Auth::user();
        $submission = RequestSubmission::where('tenant_id', $user->tenant_id)
            ->with(['template', 'employee', 'approvals.approver'])
            ->findOrFail($id);

        // TODO: Implement PDF generation using a library like Snappy or DomPDF
        // For now, we'll return a JSON check or placeholder response.
        // In a real scenario, this would return a PDF stream.
        
        return response()->json([
            'status' => 'success',
            'message' => 'PDF generation logic would go here.',
            'data' => [
                'reference' => $submission->reference_number,
                'template' => $submission->template->name,
                'employee' => $submission->employee->full_name,
                'status' => $submission->status
            ]
        ]);
    }
}
