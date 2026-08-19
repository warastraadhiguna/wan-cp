<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitKoKiRescueEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clear_instruction' => ['required', Rule::in(['ikuti_persis', 'menebak', 'memilih_sendiri'])],
            'missing_command' => ['required', Rule::in(['ambil_air', 'putar_kiri', 'maju'])],
            'sequence' => ['required', Rule::in(['ambil-maju-siram', 'siram-maju-ambil', 'maju-siram-ambil'])],
        ];
    }
}
