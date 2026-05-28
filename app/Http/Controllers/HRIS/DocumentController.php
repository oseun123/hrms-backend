<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeDocument;
use App\Services\FileUploadService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    use HandlesApiErrors;

    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display employee documents
     */
    public function index(Employee $employee)
    {
        try {
            $documents = EmployeeDocument::with('documentType')
                ->where('employee_id', $employee->id)
                ->get();

            return ApiResponse::success($documents);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching documents');
        }
    }

    /**
     * Store a newly uploaded document
     */
    public function store(Request $request, Employee $employee)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $validated = $request->validate([
                'document_type_id' => 'required|exists:document_types,id,tenant_id,' . $tenantId,
                'document_name' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
                'issue_date' => 'nullable|date',
                'expiry_date' => 'nullable|date|after:issue_date',
                'notes' => 'nullable|string',
            ]);

            // Upload file using FileUploadService
            $uploadResult = $this->fileUploadService->upload(
                $request->file('file'),
                'employee-documents',
                [
                    'employee_id' => $employee->id,
                    'validation' => ['mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
                ]
            );

            // Create document record
            $document = EmployeeDocument::create([
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
                'document_type_id' => $validated['document_type_id'],
                'document_name' => $validated['document_name'],
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['url'],
                'file_size' => $uploadResult['metadata']['size'],
                'mime_type' => $uploadResult['metadata']['mime_type'],
                'storage_driver' => $uploadResult['metadata']['driver'],
                'file_metadata' => json_encode($uploadResult['metadata']),
                'issue_date' => $validated['issue_date'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);

            return ApiResponse::created(
                $document->load('documentType'),
                'Document uploaded successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Document upload');
        }
    }

    /**
     * Display the specified document
     */
    public function show(Employee $employee, $documentId)
    {
        try {
            $document = EmployeeDocument::with('documentType')
                ->where('employee_id', $employee->id)
                ->findOrFail($documentId);

            return ApiResponse::success($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Document not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching document');
        }
    }

    public function download(Employee $employee, $documentId)
    {
        try {
            $document = EmployeeDocument::where('employee_id', $employee->id)
                ->findOrFail($documentId);

            if (!$document->file_path || !$this->fileUploadService->exists($document->file_path, $document->storage_driver)) {
                return ApiResponse::notFound('File not found');
            }

            // We can't easily use Storage::download with the FileUploadService abstraction if it returns a URL or behaves differently
            // However, assuming LocalUploadDriver uses standard storage, we can try to return a stream or use a helper
            // Let's check FileUploadService again. It has getUrl but not "download".
            // But LocalUploadDriver uses Storage::disk('public').
            // So we can use Storage::disk('public')->download($document->file_path, $document->document_name);

            // Re-reading DocumentController, it doesn't import Storage.
            // I should stick to using the service or standard Laravel Storage if I know the disk.
            // The service stores the driver name.

            $disk = $document->storage_driver === 'cloudinary' ? 'cloudinary' : 'public';

            return \Illuminate\Support\Facades\Storage::disk($disk)->download(
                $document->file_path,
                $document->document_name . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION)
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Document not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Document download');
        }
    }

    /**
     * Remove the specified document
     */
    public function destroy(Employee $employee, $documentId)
    {
        try {
            $document = EmployeeDocument::where('employee_id', $employee->id)
                ->findOrFail($documentId);

            // Delete file using FileUploadService
            if ($document->file_path) {
                $this->fileUploadService->delete(
                    $document->file_path,
                    $document->storage_driver ?? 'local'
                );
            }

            $document->delete();

            return ApiResponse::success(null, 'Document deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Document not found');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Document deletion');
        }
    }
}
