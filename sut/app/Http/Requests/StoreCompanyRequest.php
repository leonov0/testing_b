<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['required', 'string', 'max:255'],
            'company_telephone' => ['required', 'string', 'max:40'],
            'company_email' => ['required', 'email', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_mobile' => ['required', 'string', 'max:40'],
            'owner_email' => ['required', 'email', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_mobile' => ['required', 'string', 'max:40'],
            'contact_email' => ['required', 'email', 'max:255'],
        ];
    }
}
