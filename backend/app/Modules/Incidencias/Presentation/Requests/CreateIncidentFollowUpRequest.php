<?php

namespace App\Modules\Incidencias\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateIncidentFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:1', 'max:5000'],
            'file' => ['nullable', 'file'],
        ];
    }
}
