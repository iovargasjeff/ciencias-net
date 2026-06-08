<?php

namespace App\Modules\Horarios\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Horarios\Infrastructure\Models\Horario::class);
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'uuid', 'exists:carga_academica,id'],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'starts_at' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'ends_at' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'room' => ['nullable', 'string', 'min:1', 'max:100'],
        ];
    }
}
