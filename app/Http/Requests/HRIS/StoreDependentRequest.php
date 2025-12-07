<?php

namespace App\Http\Requests\HRIS;

use Illuminate\Foundation\Http\FormRequest;

class StoreDependentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'relationship' => 'required|in:spouse,child,parent,sibling,other',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'national_id' => 'nullable|string|max:50',
            'is_beneficiary' => 'boolean',
            'beneficiary_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
