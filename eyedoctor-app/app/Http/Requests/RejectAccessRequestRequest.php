<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectAccessRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'bail',
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' =>
                'Please record a reason for rejecting this request.',

            'rejection_reason.min' =>
                'Please provide a slightly more specific rejection reason.',

            'rejection_reason.max' =>
                'The rejection reason must not exceed 2,000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (
            is_string(
                $this->input('rejection_reason')
            )
        ) {
            $this->merge([
                'rejection_reason' =>
                    trim(
                        $this->input(
                            'rejection_reason'
                        )
                    ),
            ]);
        }
    }
}
