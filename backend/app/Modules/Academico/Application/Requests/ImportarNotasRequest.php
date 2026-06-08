<?php

namespace App\Modules\Academico\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportarNotasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preview' => ['sometimes', 'boolean'],
            'notas' => ['required', 'array', 'min:1'],
            'notas.*.matricula_id' => ['required', 'uuid', 'exists:matriculas,id'],
            'notas.*.estado' => ['required', 'string', Rule::in(['registrada', 'ausente', 'exonerado', 'pendiente'])],
            'notas.*.puntaje' => ['nullable', 'numeric', 'min:0'],
            'notas.*.observacion' => ['nullable', 'string', 'max:500'],
        ];
    }
}
