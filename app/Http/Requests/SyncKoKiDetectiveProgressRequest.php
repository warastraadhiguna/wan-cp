<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncKoKiDetectiveProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('cases_json'))) {
            $decoded = json_decode($this->input('cases_json'), true);
            if (is_array($decoded)) {
                $this->merge(['cases' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'cases' => ['required', 'array', 'min:1', 'max:16'],
            'cases.*.case' => ['required', 'integer', 'between:1,16'],
            'cases.*.program' => ['required', 'array', 'min:1', 'max:12'],
            'cases.*.program.*' => ['required', 'string', 'max:40'],
            'cases.*.suspects' => ['present', 'array', 'max:8'],
            'cases.*.suspects.*' => ['integer', 'between:0,11'],
            'cases.*.hint_count' => ['required', 'integer', 'between:0,3'],
            'cases.*.test_attempts' => ['required', 'integer', 'between:1,100'],
            'notebook_fix' => ['nullable', 'string', 'max:30'],
        ];
    }
}
