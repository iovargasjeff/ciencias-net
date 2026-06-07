<?php

namespace App\Http\Requests\StudentAttendance;

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
