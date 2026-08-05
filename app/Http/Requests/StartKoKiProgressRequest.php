<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartKoKiProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_name' => trim(strip_tags((string) $this->input('student_name'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_name' => ['bail', 'required', 'string', 'min:2', 'max:40'],
            'grade' => ['bail', 'required', 'integer', 'in:4,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_name.required' => 'Nama panggilan wajib diisi.',
            'student_name.min' => 'Nama panggilan minimal 2 karakter.',
            'student_name.max' => 'Nama panggilan maksimal 40 karakter.',
            'grade.required' => 'Pilih kelas terlebih dahulu.',
            'grade.in' => 'Kelas harus kelas 4 atau kelas 5.',
        ];
    }
}
