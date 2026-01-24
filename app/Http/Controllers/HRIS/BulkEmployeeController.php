<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Exports\HRIS\EmployeeTemplateExport;
use App\Imports\HRIS\EmployeeImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use App\Helpers\ApiResponse;

class BulkEmployeeController extends Controller
{
    /**
     * Download the employee import template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new EmployeeTemplateExport, 'employee_import_template.xlsx');
    }



    /**
     * Import employees from an Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'extensions:xlsx,xls,csv',
                'max:10240',
            ],
        ]);

        try {
            $import = new EmployeeImport();
            Excel::import($import, $request->file('file'));

            return ApiResponse::success([
                'summary' => [
                    'total' => $import->getTotalRows(),
                    'success' => $import->getSuccessCount(),
                    'failed' => $import->getFailedCount(),
                ],
                'errors' => $import->getErrors(),
            ], 'Import processed successfully');
        } catch (\Exception $e) {
            Log::error('Bulk import failed: ' . $e->getMessage());
            return ApiResponse::serverError('Import failed: ' . $e->getMessage());
        }
    }
}
