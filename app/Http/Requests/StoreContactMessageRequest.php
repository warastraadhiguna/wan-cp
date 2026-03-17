<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Validator;
use Throwable;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(strip_tags((string) $this->input('name'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'message' => trim(strip_tags((string) $this->input('message'))),
            'website' => trim((string) $this->input('website')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'min:3', 'max:120'],
            'email' => ['bail', 'required', 'string', 'email', 'max:120'],
            'message' => ['bail', 'required', 'string', 'min:10', 'max:3000'],
            'website' => ['nullable', 'string', 'max:0'],
            'form_token' => ['bail', 'required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.min' => 'Nama minimal 3 karakter.',
            'name.max' => 'Nama maksimal 120 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 120 karakter.',
            'message.required' => 'Pesan wajib diisi.',
            'message.min' => 'Pesan minimal 10 karakter.',
            'message.max' => 'Pesan maksimal 3000 karakter.',
            'website.max' => 'Permintaan tidak valid.',
            'form_token.required' => 'Form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $token = $this->input('form_token');

            if (! is_string($token) || $token === '') {
                return;
            }

            try {
                $timestamp = (int) Crypt::decryptString($token);
            } catch (Throwable) {
                $validator->errors()->add('form_token', 'Token form tidak valid.');

                return;
            }

            $ageInSeconds = now()->timestamp - $timestamp;

            if ($ageInSeconds < 3) {
                $validator->errors()->add('form_token', 'Pengiriman terlalu cepat. Coba lagi beberapa detik lagi.');
            }

            if ($ageInSeconds > 7200) {
                $validator->errors()->add('form_token', 'Form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
            }
        });
    }
}
