<?php

namespace App\Modules\Incidencias\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_status' => ['required', 'string', 'in:open,referred_toe,referred_psychology,parent_notified,in_progress,resolved,closed'],
            'reason' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }
}
