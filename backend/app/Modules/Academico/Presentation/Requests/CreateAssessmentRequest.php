<?php

namespace App\Modules\Academico\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'uuid', 'exists:carga_academica,id'],
            'title' => ['required', 'string', 'min:1', 'max:150'],
            'assessment_type' => ['required', 'string', 'in:exam,practice,project,participation,other'],
            'max_score' => ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'assessment_date' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'in:general,sciences,humanities'],
            'total_questions' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
