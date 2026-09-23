<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartKoKiMeetingFiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['student_name' => trim(strip_tags((string) $this->input('student_name')))]);
    }

    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'min:2', 'max:40'],
            'grade' => ['required', 'integer', 'between:4,5'],
        ];
    }
}
