<?php

namespace App\Modules\Academico\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        return [
            'matricula_id' => ['required', 'uuid', 'exists:matriculas,id'],
            'estado' => ['required', 'string', Rule::in(['registrada', 'ausente', 'exonerado', 'pendiente'])],
            'puntaje' => ['nullable', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ];
    }
}
