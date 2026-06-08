<?php

namespace App\Modules\Horarios\Presentation\Requests;

use App\Modules\Horarios\Infrastructure\Models\EventoCalendario;
use Illuminate\Foundation\Http\FormRequest;

class CreateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EventoCalendario::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:200'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'event_type' => ['required', 'string', 'in:academic,holiday,meeting,other'],
            'description' => ['nullable', 'string', 'min:1', 'max:2000'],
        ];
    }
}
