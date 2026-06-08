<?php

namespace App\Modules\Incidencias\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'uuid'],
            'incident_type' => ['required', 'string', 'min:1', 'max:100'],
            'severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'description' => ['required', 'string', 'min:1', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
