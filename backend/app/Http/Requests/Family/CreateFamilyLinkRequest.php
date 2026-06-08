<?php

namespace App\Http\Requests\Family;

use App\Models\Alumno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFamilyLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageFamilyLinks', Alumno::class) === true;
    }

    public function rules(): array
    {
        return [
            'parent_account_id' => ['required', 'uuid', 'exists:users,id'],
            'student_id' => ['required', 'uuid', 'exists:alumnos,id'],
            'relationship' => ['required', Rule::in(['padre', 'madre', 'apoderado'])],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'parent_account_id' => [
                'description' => 'Identificador de la cuenta familiar.',
                'example' => '11111111-1111-4111-8111-111111111111',
            ],
            'student_id' => [
                'description' => 'Identificador del alumno vinculado.',
                'example' => '22222222-2222-4222-8222-222222222222',
            ],
            'relationship' => [
                'description' => 'Relación familiar con el alumno.',
                'example' => 'madre',
            ],
        ];
    }
}
