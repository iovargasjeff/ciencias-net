<?php

namespace App\Http\Requests\TeacherAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateTeacherPayrollReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_planilla') === true;
    }

    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'format' => ['required', 'string', Rule::in(['pdf', 'xlsx'])],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['required', 'uuid', 'distinct', Rule::exists('docentes', 'id')],
        ];
    }
}
