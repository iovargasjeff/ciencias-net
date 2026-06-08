<?php

namespace App\Modules\Comunicados\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autenticación manejada por middleware y gate en controlador
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:200'],
            'body' => ['required', 'string', 'min:1', 'max:10000'],
            'audience_type' => ['required', 'string', 'in:all,roles,sections,accounts'],
            'audience_ids' => ['required_unless:audience_type,all', 'array', 'min:1'],
            'audience_ids.*' => ['string'],
            'publish_at' => ['nullable', 'date'],
        ];
    }
}
