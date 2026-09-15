<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteKoKiDetectiveCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'case' => ['required', 'integer', 'between:1,16'],
            'program' => ['required', 'array', 'min:1', 'max:12'],
            'program.*' => ['required', 'string', 'max:40'],
            'suspects' => ['present', 'array', 'max:8'],
            'suspects.*' => ['integer', 'between:0,11'],
            'hint_count' => ['nullable', 'integer', 'between:0,3'],
            'test_attempts' => ['required', 'integer', 'between:1,100'],
        ];
    }
}
