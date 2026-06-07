<?php

namespace App\Http\Requests\TeacherAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeacherAttendanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_planilla') === true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'uuid', Rule::exists('docentes', 'id')],
            'date' => ['required', 'date'],
            'adjustment_type' => ['required', 'string', Rule::in(['add', 'subtract'])],
            'minutes' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
