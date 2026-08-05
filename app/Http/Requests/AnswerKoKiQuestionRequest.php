<?php

namespace App\Http\Requests;

use App\Services\KoKiLessonService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnswerKoKiQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'question_number' => (int) $this->route('question'),
        ]);
    }

    public function rules(KoKiLessonService $lessonService): array
    {
        $question = $lessonService->question((int) $this->input('question_number'));

        return [
            'question_number' => ['required', 'integer', 'between:1,4'],
            'answer' => [
                'bail',
                'required',
                'string',
                Rule::in(array_keys($question['answers'] ?? [])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'answer.required' => 'Pilih satu jawaban sebelum melanjutkan.',
            'answer.in' => 'Jawaban yang dipilih tidak valid.',
        ];
    }
}
