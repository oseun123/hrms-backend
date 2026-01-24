<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\DocumentType;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    use HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $types = DocumentType::active()->get();
            return ApiResponse::success($types);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching document types');
        }
    }
}
