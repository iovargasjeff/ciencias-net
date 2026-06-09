<?php

namespace App\Modules\Incidencias\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'format' => ['required', 'string', 'in:pdf,xlsx'],
            'status' => ['nullable', 'string', 'in:open,referred_toe,referred_psychology,parent_notified,in_progress,resolved,closed'],
            'severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
        ];
    }
}
