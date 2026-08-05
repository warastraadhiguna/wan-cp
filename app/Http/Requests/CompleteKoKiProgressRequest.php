<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteKoKiProgressRequest extends FormRequest
{
    public const REFLECTION_CHOICE = 'Agar tahu apa yang harus dilakukan';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reflection' => trim(strip_tags((string) $this->input('reflection'))),
            'reflection_choice' => trim(strip_tags((string) $this->input('reflection_choice'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'exit_ticket_answers' => ['required', 'array:q1,q2,q3'],
            'exit_ticket_answers.q1' => ['required', 'string', Rule::in(['not_quite'])],
            'exit_ticket_answers.q2' => ['required', 'string', Rule::in(['true'])],
            'exit_ticket_answers.q3' => ['required', 'string', Rule::in(['not_quite'])],
            'reflection' => ['bail', 'required', 'string', 'min:3', 'max:200'],
            'reflection_choice' => ['required', 'string', Rule::in([self::REFLECTION_CHOICE])],
        ];
    }

    public function messages(): array
    {
        return [
            'exit_ticket_answers.required' => 'Jawab ketiga pernyataan exit ticket.',
            'exit_ticket_answers.q1.required' => 'Pilih jawaban untuk pernyataan pertama.',
            'exit_ticket_answers.q2.required' => 'Pilih jawaban untuk pernyataan kedua.',
            'exit_ticket_answers.q3.required' => 'Pilih jawaban untuk pernyataan ketiga.',
            'exit_ticket_answers.q1.in' => 'Periksa kembali jawaban pernyataan pertama.',
            'exit_ticket_answers.q2.in' => 'Periksa kembali jawaban pernyataan kedua.',
            'exit_ticket_answers.q3.in' => 'Periksa kembali jawaban pernyataan ketiga.',
            'reflection.required' => 'Refleksi wajib diisi.',
            'reflection.min' => 'Refleksi minimal 3 karakter.',
            'reflection.max' => 'Refleksi maksimal 200 karakter.',
            'reflection_choice.required' => 'Pilih alasan komputer membutuhkan program.',
            'reflection_choice.in' => 'Pilihan refleksi tidak valid.',
        ];
    }
}
