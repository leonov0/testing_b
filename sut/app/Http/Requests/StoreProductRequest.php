<?php

namespace App\Http\Requests;

use App\Services\Gtin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'gtin' => [
                'required',
                Rule::unique('products', 'gtin')->ignore($productId),
                function (string $attribute, mixed $value, callable $fail): void {
                    if (! Gtin::isValidFormat($value)) {
                        $fail('The GTIN must be a sequence of 13 or 14 digits.');
                    }
                },
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_fr' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'description_fr' => ['nullable', 'string', 'max:5000'],
            'brand' => ['required', 'string', 'max:255'],
            'country_of_origin' => ['required', 'string', 'max:255'],
            'weight_gross' => ['required', 'numeric', 'gt:0'],
            'weight_net' => ['required', 'numeric', 'gt:0', 'lte:weight_gross'],
            'weight_unit' => ['required', 'string', 'max:8'],
            'image' => ['sometimes', 'nullable', 'file'],
        ];
    }
}
