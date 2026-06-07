<?php

namespace App\Http\Requests\StudentAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRecognitionEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'auxiliar']) === true;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', 'string', Rule::in(['confirmed', 'rejected', 'reassigned'])],
            'matched_student_id' => ['required_if:outcome,reassigned', 'uuid', Rule::exists('alumnos', 'id')],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
