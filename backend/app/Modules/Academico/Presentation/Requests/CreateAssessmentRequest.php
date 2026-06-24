<?php

namespace App\Modules\Academico\Presentation\Requests;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grade_id' => ['required', 'uuid', 'exists:grados,id'],
            'section_id' => ['required', 'uuid', 'exists:secciones,id'],
            'course_id' => ['required', 'uuid', 'exists:cursos,id'],
            'teaching_assignment_id' => ['required', 'uuid', 'exists:carga_academica,id'],
            'title' => ['required', 'string', 'min:1', 'max:150'],
            'assessment_type' => ['required', 'string', 'in:exam,practice,project,participation,other'],
            'max_score' => ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'assessment_date' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'in:general,sciences,humanities'],
            'total_questions' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $assignment = CargaAcademica::with('seccion')->find($this->input('teaching_assignment_id'));
                if (! $assignment) {
                    return;
                }

                if ($assignment->seccion_id !== $this->input('section_id')) {
                    $validator->errors()->add('section_id', 'La sección no corresponde a la carga académica seleccionada.');
                }

                if ($assignment->curso_id !== $this->input('course_id')) {
                    $validator->errors()->add('course_id', 'El curso no corresponde a la carga académica seleccionada.');
                }

                if ($assignment->seccion?->grado_id !== $this->input('grade_id')) {
                    $validator->errors()->add('grade_id', 'El grado no corresponde a la carga académica seleccionada.');
                }
            },
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
