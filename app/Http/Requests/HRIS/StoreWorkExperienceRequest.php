<?php

namespace App\Http\Requests\HRIS;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkExperienceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'responsibilities' => 'nullable|string',
            'reason_for_leaving' => 'nullable|string',
        ];
    }
}
