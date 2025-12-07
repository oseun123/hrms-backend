<?php

namespace App\Http\Requests\HRIS;

class UpdateDependentRequest extends StoreDependentRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'relationship' => 'sometimes|required|in:spouse,child,parent,sibling,other',
            'date_of_birth' => 'sometimes|required|date|before:today',
            'gender' => 'sometimes|required|in:male,female,other',
            'national_id' => 'nullable|string|max:50',
            'is_beneficiary' => 'boolean',
            'beneficiary_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
