<?php

namespace App\Http\Requests\HRIS;

class UpdateEmergencyContactRequest extends StoreEmergencyContactRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'relationship' => 'sometimes|required|string|max:100',
            'phone' => 'sometimes|required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_primary' => 'boolean',
        ];
    }
}
