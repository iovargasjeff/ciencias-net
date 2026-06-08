<?php

namespace App\Modules\Academico\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAcademicReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', 'in:pdf,xlsx'],
            'report_type' => ['required', 'string', 'in:report_card,grade_summary,ranking'],
            'academic_period_id' => ['nullable', 'uuid'],
            'section_id' => ['nullable', 'uuid'],
            'student_id' => ['nullable', 'uuid'],
        ];
    }
}
