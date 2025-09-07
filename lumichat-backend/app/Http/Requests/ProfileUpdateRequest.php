<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public const COURSES = ['BSIT','EDUC','CAS','CRIM','BLIS','MIDWIFERY','BSHM','BSBA'];
    public const YEARS   = ['1st year','2nd year','3rd year','4th year'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Name: strip tags + collapse whitespace
        $name = strip_tags((string) $this->input('name', ''));
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        // Email: strip tags, trim, lowercase
        $email = strip_tags((string) $this->input('email', ''));
        $email = strtolower(trim($email));

        // Course: UPPER + whitelist
        $course = strtoupper(trim((string) $this->input('course', '')));
        $course = in_array($course, self::COURSES, true) ? $course : null;

        // Year: whitelist
        $year = trim((string) $this->input('year_level', ''));
        $year = in_array($year, self::YEARS, true) ? $year : null;

        // Phone: digits only; normalize PH 09xxxxxxxxx -> 639xxxxxxxxx
        $phone = preg_replace('/\D+/', '', (string) $this->input('contact_number', ''));
        if ($phone && str_starts_with($phone, '09') && strlen($phone) === 11) {
            $phone = '63' . substr($phone, 1);
        }
        $phone = $phone ?: null;

        $this->merge([
            'name'           => $name,
            'email'          => $email,
            'course'         => $course,
            'year_level'     => $year,
            'contact_number' => $phone,
        ]);
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => [
                'bail','required','string','min:2','max:100',
                'regex:/^[\p{L}\p{M}][\p{L}\p{M}\s\.\'\-]*$/u',
            ],
            'email' => [
                'bail','required','string','max:255','lowercase',
                'email:rfc,dns',
                Rule::unique('tbl_users','email')->ignore($userId, 'id'),
            ],
            'course'         => ['nullable','in:'.implode(',', self::COURSES)],
            'year_level'     => ['nullable','in:'.implode(',', self::YEARS)],
            'contact_number' => ['nullable','digits_between:10,15'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex'   => 'The name may contain letters, spaces, dots, apostrophes, and hyphens only, and must start with a letter.',
            'email.unique' => 'This email is already in use.',
        ];
    }

    public function attributes(): array
    {
        return [
            'year_level'     => 'year level',
            'contact_number' => 'contact number',
        ];
    }
}
