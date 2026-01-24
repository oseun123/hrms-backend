<?php

namespace App\Http\Requests\HRIS;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialDetailsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_name' => 'sometimes|required|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'account_number' => 'sometimes|required|string|max:50',
            'account_name' => 'sometimes|required|string|max:255',
            'account_type' => 'nullable|in:savings,current,checking',
            'swift_code' => 'nullable|string|max:20',
            'iban' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'tax_status' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:50',
            'pension_number' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
            'current_salary' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|size:3',
            'payment_frequency' => 'nullable|in:monthly,bi-weekly,weekly,daily',
            'payment_method' => 'nullable|in:bank_transfer,cash,cheque',
        ];
    }
}
