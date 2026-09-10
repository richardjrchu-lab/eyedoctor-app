<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreAccessRequestRequest extends FormRequest
{
    /**
     * Applicant profession choices accepted by the server.
     *
     * Profession describes the applicant's real-world background.
     * It does NOT determine the Laravel authorization role.
     *
     * @var array<string, string>
     */
    public const PROFESSION_OPTIONS = [
        'ophthalmologist' => 'Ophthalmologist',
        'optometrist' => 'Optometrist',
        'physician' => 'Physician',
        'nurse' => 'Nurse',
        'medical_technologist' => 'Medical Technologist',
        'other_healthcare_professional' => 'Other Healthcare Professional',
        'healthcare_researcher' => 'Healthcare Researcher',
        'health_sciences_student_trainee' => 'Medical / Health Sciences Student or Trainee',
        'other' => 'Other',
    ];

    /**
     * Accepted professional-verification document categories.
     *
     * @var array<string, string>
     */
    public const PROOF_TYPE_OPTIONS = [
        'professional_license' => 'Professional License / Registration Card',
        'institution_id' => 'Employee / Institution / School ID',
        'official_affiliation_document' => 'Official Affiliation Document',
    ];

    /**
     * Maximum uploaded verification-document size.
     */
    public const MAX_PROOF_SIZE = '8mb';

    /**
     * This is a public application endpoint.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize harmless text input before validation.
     *
     * Authorization state, account roles, lifecycle state, proof metadata,
     * and audit fields are NEVER derived from browser-submitted values.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->cleanString(
                $this->input('full_name')
            ),

            'email' => $this->normalizeEmail(
                $this->input('email')
            ),

            'profession' => $this->cleanString(
                $this->input('profession')
            ),

            'institution' => $this->cleanString(
                $this->input('institution')
            ),

            'department_position' => $this->cleanNullableString(
                $this->input('department_position')
            ),

            'license_registration_number' => $this->cleanNullableString(
                $this->input('license_registration_number')
            ),

            'proof_type' => $this->cleanString(
                $this->input('proof_type')
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => [
                'bail',
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:254',
            ],

            'profession' => [
                'bail',
                'required',
                'string',
                Rule::in(
                    array_keys(self::PROFESSION_OPTIONS)
                ),
            ],

            'institution' => [
                'bail',
                'required',
                'string',
                'min:2',
                'max:200',
            ],

            'department_position' => [
                'nullable',
                'string',
                'max:150',
            ],

            'license_registration_number' => [
                'nullable',
                'string',
                'max:120',
                'required_if:proof_type,professional_license',
            ],

            'proof_type' => [
                'bail',
                'required',
                'string',
                Rule::in(
                    array_keys(self::PROOF_TYPE_OPTIONS)
                ),
            ],

            /*
             * File::types validates the uploaded file based on its detected
             * MIME type rather than trusting only the filename extension.
             *
             * SVG, HTML, executables, archives, Office documents, and other
             * active file formats are deliberately not accepted.
             */
            'proof_document' => [
                'bail',
                'required',
                File::types([
                    'pdf',
                    'jpg',
                    'jpeg',
                    'png',
                ])->max(self::MAX_PROOF_SIZE),
            ],

            'privacy_consent' => [
                'required',
                'accepted',
            ],

            'appropriate_use_consent' => [
                'required',
                'accepted',
            ],

            /*
             * Defense in depth:
             *
             * These values belong exclusively to trusted server-side code.
             * An applicant must never be able to create an administrator,
             * self-approve, choose a lifecycle state, or supply trusted proof
             * metadata from the browser.
             */
            'role' => ['prohibited'],
            'password' => ['prohibited'],
            'status' => ['prohibited'],
            'public_id' => ['prohibited'],
            'email_normalized' => ['prohibited'],
            'license_registration_last4' => ['prohibited'],
            'proof_disk' => ['prohibited'],
            'proof_object_key' => ['prohibited'],
            'proof_mime_type' => ['prohibited'],
            'proof_size_bytes' => ['prohibited'],
            'proof_sha256' => ['prohibited'],
            'proof_uploaded_at' => ['prohibited'],
            'proof_deleted_at' => ['prohibited'],
            'submission_count' => ['prohibited'],
            'last_submitted_at' => ['prohibited'],

            /*
             * Consent itself comes from the applicant, but the trusted
             * timestamps and policy-version identifiers are assigned only
             * by RETINA after validation succeeds.
             */
            'privacy_consent_at' => ['prohibited'],
            'privacy_notice_version' => ['prohibited'],
            'appropriate_use_consent_at' => ['prohibited'],
            'appropriate_use_notice_version' => ['prohibited'],

            'email_verification_sent_at' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'reviewed_by' => ['prohibited'],
            'reviewed_at' => ['prohibited'],
            'rejection_reason' => ['prohibited'],
            'approved_user_id' => ['prohibited'],
            'account_setup_sent_at' => ['prohibited'],
        ];
    }

    /**
     * Human-friendly messages for the public application form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' =>
                'Please enter your full name.',

            'email.required' =>
                'Please enter your professional or institutional email address.',

            'email.email' =>
                'Please enter a valid email address.',

            'profession.required' =>
                'Please select your profession or training category.',

            'profession.in' =>
                'Please select a valid profession or training category.',

            'institution.required' =>
                'Please enter your institution or organization.',

            'license_registration_number.required_if' =>
                'Please enter the registration number shown on the professional license you are submitting.',

            'proof_type.required' =>
                'Please select the type of verification document you are providing.',

            'proof_type.in' =>
                'Please select a valid verification-document type.',

            'proof_document.required' =>
                'Please upload a professional verification document.',

            'proof_document.mimes' =>
                'The verification document must be a PDF, JPG, JPEG, or PNG file.',

            'proof_document.max' =>
                'The verification document must not exceed 8 MB.',

            'privacy_consent.accepted' =>
                'You must acknowledge the privacy notice before submitting your request.',

            'appropriate_use_consent.accepted' =>
                'You must acknowledge the RETINA appropriate-use notice before submitting your request.',

            '*.prohibited' =>
                'The request contained a field that applicants are not permitted to set.',
        ];
    }

    /**
     * Options supplied to the public form.
     *
     * @return array<string, string>
     */
    public static function professionOptions(): array
    {
        return self::PROFESSION_OPTIONS;
    }

    /**
     * Options supplied to the public form.
     *
     * @return array<string, string>
     */
    public static function proofTypeOptions(): array
    {
        return self::PROOF_TYPE_OPTIONS;
    }

    private function normalizeEmail(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return mb_strtolower(trim($value));
    }

    private function cleanString(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return trim($value);
    }

    private function cleanNullableString(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
