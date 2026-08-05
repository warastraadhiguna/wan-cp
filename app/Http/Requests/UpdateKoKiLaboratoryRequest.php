<?php

namespace App\Http\Requests;

use App\Services\KoKiLaboratoryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKoKiLaboratoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $action = $this->string('action')->toString();

        return [
            'action' => ['required', 'string', Rule::in(['try_command', 'answer_question', 'classify'])],
            'command' => [
                Rule::requiredIf($action === 'try_command'),
                'string',
                Rule::in(array_keys(KoKiLaboratoryService::STRANGE_COMMANDS)),
            ],
            'strange_answer' => [
                Rule::requiredIf($action === 'answer_question'),
                'string',
                Rule::in(array_keys(KoKiLaboratoryService::STRANGE_QUESTION['options'])),
            ],
            'classification_item' => [
                Rule::requiredIf($action === 'classify'),
                'string',
                Rule::in(array_column(KoKiLaboratoryService::CLASSIFICATIONS, 'id')),
            ],
            'classification_category' => [
                Rule::requiredIf($action === 'classify'),
                'string',
                Rule::in(['human', 'computer']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'Aktivitas laboratorium tidak valid.',
            'command.in' => 'Perintah laboratorium tidak valid.',
            'strange_answer.in' => 'Jawaban yang dipilih tidak valid.',
            'classification_item.in' => 'Pernyataan klasifikasi tidak valid.',
            'classification_category.in' => 'Kategori klasifikasi tidak valid.',
        ];
    }
}
