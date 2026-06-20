<?php

namespace App\Http\Requests\Academic;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', PeriodoAcademico::class) === true;
    }

    public function rules(): array
    {
        return match ((string) $this->route()->getName()) {
            'api.v1.academic-periods.store' => [
                'name' => ['required', 'string', 'max:100'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'status' => ['sometimes', Rule::in(['draft', 'active', 'closed'])],
                'terms' => ['sometimes', 'array', 'size:4'],
                'terms.*.name' => ['required_with:terms', 'string', 'max:60'],
                'terms.*.start_date' => ['required_with:terms', 'date'],
                'terms.*.end_date' => ['required_with:terms', 'date'],
            ],
            'api.v1.academic-periods.update' => [
                'name' => ['sometimes', 'string', 'max:100'],
                'start_date' => ['sometimes', 'date'],
                'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
                'status' => ['sometimes', Rule::in(['draft', 'active', 'closed'])],
            ],
            'api.v1.grades.store' => [
                'catalog_code' => ['required_without:name', 'string', 'max:40'],
                'name' => ['required_without:catalog_code', 'string', 'max:100'],
                'level' => ['sometimes', Rule::in(['inicial', 'primaria', 'secundaria'])],
                'order' => ['sometimes', 'integer', 'min:1'],
                'academic_period_id' => ['required', 'uuid', 'exists:periodos_academicos,id'],
            ],
            'api.v1.sections.store' => [
                'grade_id' => ['required', 'uuid', 'exists:grados,id'],
                'name' => ['required', 'string', 'max:50'],
                'capacity' => ['required', 'integer', 'min:1'],
            ],
            'api.v1.courses.store' => [
                'grade_id' => ['required', 'uuid', 'exists:grados,id'],
                'code' => ['required', 'string', 'max:30', 'unique:cursos,codigo'],
                'name' => ['required', 'string', 'max:150'],
                'description' => ['sometimes', 'string', 'max:1000'],
            ],
            'api.v1.enrollments.store' => [
                'student_id' => ['required', 'uuid', 'exists:alumnos,id'],
                'grade_id' => ['required', 'uuid', 'exists:grados,id'],
                'section_id' => ['required', 'uuid', 'exists:secciones,id'],
                'academic_period_id' => ['required', 'uuid', 'exists:periodos_academicos,id'],
                'enrolled_at' => ['sometimes', 'date'],
            ],
            'api.v1.teaching-assignments.store' => [
                'teacher_id' => ['required', 'uuid', 'exists:docentes,id'],
                'grade_id' => ['required', 'uuid', 'exists:grados,id'],
                'course_id' => ['required', 'uuid', 'exists:cursos,id'],
                'section_id' => ['required', 'uuid', 'exists:secciones,id'],
                'academic_period_id' => ['required', 'uuid', 'exists:periodos_academicos,id'],
            ],
            default => [],
        };
    }

    public function bodyParameters(): array
    {
        return match ((string) $this->route()->getName()) {
            'api.v1.academic-periods.store', 'api.v1.academic-periods.update' => [
                'name' => [
                    'description' => 'Nombre del periodo académico.',
                    'example' => 'Año académico 2026',
                ],
                'start_date' => [
                    'description' => 'Fecha de inicio del periodo.',
                    'example' => '2026-03-01',
                ],
                'end_date' => [
                    'description' => 'Fecha de cierre del periodo.',
                    'example' => '2026-12-20',
                ],
                'status' => [
                    'description' => 'Estado operativo del periodo.',
                    'example' => 'active',
                ],
            ],
            'api.v1.grades.store' => [
                'name' => [
                    'description' => 'Nombre del grado.',
                    'example' => 'Quinto de secundaria',
                ],
                'level' => [
                    'description' => 'Nivel académico del grado.',
                    'example' => 'secundaria',
                ],
                'order' => [
                    'description' => 'Orden del grado dentro del nivel.',
                    'example' => 5,
                ],
                'academic_period_id' => [
                    'description' => 'Identificador del periodo académico.',
                    'example' => '33333333-3333-4333-8333-333333333333',
                ],
            ],
            'api.v1.sections.store' => [
                'grade_id' => [
                    'description' => 'Identificador del grado.',
                    'example' => '44444444-4444-4444-8444-444444444444',
                ],
                'name' => [
                    'description' => 'Nombre de la sección.',
                    'example' => 'A',
                ],
                'capacity' => [
                    'description' => 'Capacidad máxima de alumnos.',
                    'example' => 30,
                ],
            ],
            'api.v1.courses.store' => [
                'code' => [
                    'description' => 'Código único del curso.',
                    'example' => 'MAT-5S',
                ],
                'name' => [
                    'description' => 'Nombre del curso.',
                    'example' => 'Matemática',
                ],
                'description' => [
                    'description' => 'Descripción breve del curso.',
                    'example' => 'Curso anual de matemática para quinto de secundaria.',
                ],
            ],
            'api.v1.enrollments.store' => [
                'student_id' => [
                    'description' => 'Identificador del alumno.',
                    'example' => '55555555-5555-4555-8555-555555555555',
                ],
                'section_id' => [
                    'description' => 'Identificador de la sección.',
                    'example' => '66666666-6666-4666-8666-666666666666',
                ],
                'academic_period_id' => [
                    'description' => 'Identificador del periodo académico.',
                    'example' => '33333333-3333-4333-8333-333333333333',
                ],
                'enrolled_at' => [
                    'description' => 'Fecha de matrícula.',
                    'example' => '2026-03-05',
                ],
            ],
            'api.v1.teaching-assignments.store' => [
                'teacher_id' => [
                    'description' => 'Identificador del docente.',
                    'example' => '77777777-7777-4777-8777-777777777777',
                ],
                'course_id' => [
                    'description' => 'Identificador del curso.',
                    'example' => '88888888-8888-4888-8888-888888888888',
                ],
                'section_id' => [
                    'description' => 'Identificador de la sección.',
                    'example' => '66666666-6666-4666-8666-666666666666',
                ],
                'academic_period_id' => [
                    'description' => 'Identificador del periodo académico.',
                    'example' => '33333333-3333-4333-8333-333333333333',
                ],
            ],
            default => [],
        };
    }
}
