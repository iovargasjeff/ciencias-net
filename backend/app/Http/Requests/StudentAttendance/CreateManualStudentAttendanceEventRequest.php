<?php

namespace App\Http\Requests\StudentAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateManualStudentAttendanceEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'auxiliar']) === true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', Rule::exists('alumnos', 'id')],
            'event_type' => ['required', 'string', Rule::in(['entry', 'exit', 'absence', 'late'])],
            'occurred_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
