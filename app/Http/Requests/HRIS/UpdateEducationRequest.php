<?php

namespace App\Http\Requests\HRIS;

class UpdateEducationRequest extends StoreEducationRequest
{
    public function rules()
    {
        return [
            'institution' => 'sometimes|required|string|max:255',
            'degree' => 'sometimes|required|string|max:100',
            'field_of_study' => 'sometimes|required|string|max:100',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after:start_date',
            'grade' => 'nullable|string|max:20',
            'is_highest' => 'boolean',
        ];
    }
}
