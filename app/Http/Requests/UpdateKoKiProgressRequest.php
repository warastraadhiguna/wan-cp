<?php

namespace App\Http\Requests;

use App\Services\KoKiProgressService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKoKiProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $prepared = [];

        if ($this->has('last_program')) {
            $prepared['last_program'] = array_map(
                fn (mixed $command): string => trim(strip_tags((string) $command)),
                (array) $this->input('last_program', []),
            );
        }

        $this->merge($prepared);
    }

    public function rules(): array
    {
        return [
            'last_phase' => ['required', 'string', Rule::in(['activity'])],
            'current_level' => ['required', 'integer', 'between:1,'.KoKiProgressService::MISSION_COUNT],
            'last_program' => ['sometimes', 'array', 'max:100'],
            'last_program.*' => ['string', Rule::in(['MOVE_FORWARD', 'TURN_LEFT', 'TURN_RIGHT'])],
            'increment_attempt' => ['sometimes', 'boolean'],
            'completed_level' => ['sometimes', 'integer', 'between:1,'.KoKiProgressService::MISSION_COUNT, 'same:current_level'],
            'prediction' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['yes', 'no', 'unsure']),
                Rule::prohibitedIf($this->integer('current_level') !== 2),
            ],
            'level_state' => [
                'sometimes',
                'array:ran_starter,observed_bug,corrected_bug',
                Rule::prohibitedIf($this->integer('current_level') !== 3),
            ],
            'level_state.ran_starter' => ['sometimes', 'boolean'],
            'level_state.observed_bug' => ['sometimes', 'boolean'],
            'level_state.corrected_bug' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'last_phase.required' => 'Fase aktivitas wajib dikirim.',
            'last_phase.in' => 'Fase aktivitas tidak valid.',
            'current_level.between' => 'Level aktif tidak valid.',
            'last_program.max' => 'Program terakhir terlalu panjang.',
            'last_program.*.in' => 'Program berisi command yang tidak valid.',
            'prediction.in' => 'Prediksi yang dipilih tidak valid.',
            'prediction.prohibited' => 'Prediksi hanya tersedia pada Misi 2.',
            'level_state.prohibited' => 'State debugging hanya tersedia pada Misi 3.',
            'completed_level.same' => 'Level selesai harus sama dengan level aktif.',
        ];
    }
}
