<?php

namespace App\Http\Requests\HRIS;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'institution' => 'required|string|max:255',
            'degree' => 'required|string|max:100',
            'field_of_study' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'grade' => 'nullable|string|max:20',
            'is_highest' => 'boolean',
        ];
    }
}
