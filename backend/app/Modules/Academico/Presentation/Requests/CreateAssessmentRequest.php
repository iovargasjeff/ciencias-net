<?php

namespace App\Modules\Academico\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'uuid', 'exists:carga_academica,id'],
            'title' => ['required', 'string', 'min:1', 'max:150'],
            'assessment_type' => ['required', 'string', 'in:exam,practice,project,participation,other'],
            'max_score' => ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'assessment_date' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'in:general,sciences,humanities'],
            'total_questions' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'teaching_assignment_id' => [
                'description' => 'Identificador de la carga académica.',
                'example' => '99999999-9999-4999-8999-999999999999',
            ],
            'title' => [
                'description' => 'Título de la evaluación.',
                'example' => 'Semanal 1 - I Bimestre',
            ],
            'assessment_type' => [
                'description' => 'Tipo de evaluación.',
                'example' => 'exam',
            ],
            'max_score' => [
                'description' => 'Puntaje máximo permitido.',
                'example' => '20.00',
            ],
            'assessment_date' => [
                'description' => 'Fecha programada de la evaluación.',
                'example' => '2026-04-15',
            ],
            'channel' => [
                'description' => 'Canal académico asociado.',
                'example' => 'sciences',
            ],
            'total_questions' => [
                'description' => 'Cantidad total de preguntas.',
                'example' => 40,
            ],
        ];
    }
}
