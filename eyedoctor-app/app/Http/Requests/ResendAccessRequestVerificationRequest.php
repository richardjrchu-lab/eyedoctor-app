<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ResendAccessRequestVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],

            'cf-turnstile-response' => [
                'bail',
                'required',
                'string',
                'max:2048',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => Str::lower(
                    trim($this->input('email'))
                ),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' =>
                'Please enter the email used for your access request.',

            'email.email' =>
                'Please enter a valid email address.',

            'cf-turnstile-response.required' =>
                'Please complete the security verification.',
        ];
    }
}
