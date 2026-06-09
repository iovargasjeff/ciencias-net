<?php

namespace App\Modules\Psicologia\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePsychologyCareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'uuid'],
            'occurred_at' => ['required', 'date'],
            'summary' => ['required', 'string', 'min:1', 'max:5000'],
            'confidential_notes' => ['nullable', 'string', 'min:1', 'max:10000'],
            'incident_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}
