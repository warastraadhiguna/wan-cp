<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKoKiRescueProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'integer', 'between:1,6'],
            'program' => ['required', 'array', 'min:1', 'max:42'],
            'program.*' => ['required', 'string', Rule::in(['MOVE_FORWARD', 'TURN_LEFT', 'TURN_RIGHT', 'TAKE_WATER', 'WATER'])],
            'prediction' => ['required', 'array:x,y'],
            'prediction.x' => ['required', 'integer', 'between:0,6'],
            'prediction.y' => ['required', 'integer', 'between:0,6'],
        ];
    }
}
