<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Services\ProfileCompletenessService;
use App\Helpers\ApiResponse;

class ProfileCompletenessController extends Controller
{
    protected $service;

    public function __construct(ProfileCompletenessService $service)
    {
        $this->service = $service;
    }

    /**
     * Get detailed profile completeness breakdown
     */
    public function show(Employee $employee)
    {
        $this->service->calculate($employee);
        $breakdown = $this->service->getBreakdown($employee);

        return ApiResponse::success($breakdown);
    }
}
