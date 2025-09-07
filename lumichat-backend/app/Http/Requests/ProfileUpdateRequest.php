<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $name  = trim(preg_replace('/\s+/', ' ', (string) $this->input('name', '')));
        $email = strtolower(trim((string) $this->input('email', '')));

        $courses = ['BSIT','EDUC','CAS','CRIM','BLIS','MIDWIFERY','BSHM','BSBA'];
        $years   = ['1st year','2nd year','3rd year','4th year'];

        $course = in_array($this->input('course'), $courses, true) ? $this->input('course') : null;
        $year   = in_array($this->input('year_level'), $years, true) ? $this->input('year_level') : null;

        // keep digits only for phone
        $contact = preg_replace('/\D+/', '', (string) $this->input('contact_number', ''));

        $this->merge([
            'name'            => $name,
            'email'           => $email,
            'course'          => $course,
            'year_level'      => $year,
            'contact_number'  => $contact,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:100'],
            'email' => [
                'required','email:rfc,dns',
                Rule::unique('tbl_users','email')->ignore($this->user()?->id),
            ],
            'course' => ['nullable','in:BSIT,EDUC,CAS,CRIM,BLIS,MIDWIFERY,BSHM,BSBA'],
            'year_level' => ['nullable','in:1st year,2nd year,3rd year,4th year'],
            'contact_number' => ['nullable','digits_between:10,15'],
        ];
    }

    public function attributes(): array
    {
        return [
            'year_level' => 'year level',
            'contact_number' => 'contact number',
        ];
    }
}
