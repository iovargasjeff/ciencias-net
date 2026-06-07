<?php

namespace App\Http\Requests\TeacherAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeacherRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_planilla') === true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'uuid', Rule::exists('docentes', 'id')],
            'hourly_rate' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
