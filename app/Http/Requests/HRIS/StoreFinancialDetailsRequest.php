<?php

namespace App\Http\Requests\HRIS;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialDetailsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'account_type' => 'nullable|in:savings,current,checking',
            'swift_code' => 'nullable|string|max:20',
            'iban' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'tax_status' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:50',
            'pension_number' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
            'current_salary' => 'required|numeric|min:0',
            'salary_currency' => 'required|string|size:3',
            'payment_frequency' => 'required|in:monthly,bi-weekly,weekly,daily',
            'payment_method' => 'nullable|in:bank_transfer,cash,cheque',
        ];
    }
}
