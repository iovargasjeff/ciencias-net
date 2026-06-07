<?php

namespace App\Http\Requests\TeacherAttendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignClassSessionSubstituteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_planilla') === true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'uuid', Rule::exists('docentes', 'id')],
        ];
    }
}
