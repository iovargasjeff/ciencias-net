<?php

namespace App\Modules\Asistencia\Presentation\Requests\StudentAttendance;

use Illuminate\Foundation\Http\FormRequest;

class CloseStudentAttendanceDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'auxiliar']) === true;
    }

    public function rules(): array
    {
        return ['date' => ['required', 'date']];
    }
}
