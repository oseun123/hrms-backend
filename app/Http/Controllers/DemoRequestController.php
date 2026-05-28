<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\DemoRequest;
use App\Notifications\DemoRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class DemoRequestController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:150',
            'email'        => 'required|email|max:150',
            'company'      => 'required|string|max:150',
            'phone'        => 'nullable|string|max:30',
            'company_size' => 'nullable|string|max:50',
            'message'      => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError('Validation failed', $validator->errors());
        }

        $demo = DemoRequest::create($validator->validated());

        // Notify all configured demo receivers
        $receiversEnv = env('DEMO_REQUEST_RECEIVERS', '');
        $receivers = array_filter(array_map('trim', explode(',', $receiversEnv)));

        if (!empty($receivers)) {
            Notification::route('mail', $receivers)
                ->notify(new DemoRequestNotification($demo));
        }

        return ApiResponse::success(
            ['id' => $demo->id],
            'Your demo request has been received. We will be in touch shortly.',
            201
        );
    }
}
