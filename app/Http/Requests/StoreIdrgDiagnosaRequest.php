<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdrgDiagnosaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'noreg' => ['required', 'string', 'max:100'],
            'icd' => ['required', 'string', 'max:30'],
            'diagnosa' => ['nullable', 'string', 'max:255'],
        ];
    }
}
