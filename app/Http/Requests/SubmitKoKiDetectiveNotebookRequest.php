<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitKoKiDetectiveNotebookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['fix' => ['required', Rule::in(['TURN_LEFT', 'TURN_RIGHT', 'MOVE_FORWARD'])]];
    }
}
