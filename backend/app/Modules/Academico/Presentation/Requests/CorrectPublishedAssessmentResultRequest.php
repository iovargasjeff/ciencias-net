<?php

namespace App\Modules\Academico\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectPublishedAssessmentResultRequest extends FormRequest
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
            'score' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
