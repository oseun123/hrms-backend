<?php

namespace App\Http\Requests\HRIS;

class UpdateAddressRequest extends StoreAddressRequest
{
    public function rules()
    {
        return [
            'address_type' => 'sometimes|required|in:home,work,mailing',
            'address_line1' => 'sometimes|required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'sometimes|required|string|max:100',
            'is_primary' => 'boolean',
        ];
    }
}
