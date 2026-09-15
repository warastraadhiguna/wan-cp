<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeginKoKiDetectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['answer' => ['required', Rule::in(['robot_nakal', 'komputer_marah', 'ada_bug', 'pohon_salah'])]];
    }
}
