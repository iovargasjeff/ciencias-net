<?php

namespace App\UseCases\Assessments;

use App\Models\Examen;

class CreateAssessment
{
    public function execute(array $data): Examen
    {
        // Mapeo del API schema al DB schema
        return Examen::create([
            'carga_academica_id' => $data['teaching_assignment_id'],
            'titulo' => $data['title'],
            'periodo_nombre' => $data['assessment_type'],
            'canal' => match ($data['channel'] ?? 'general') {
                'sciences' => 'ciencias',
                'humanities' => 'letras',
                default => 'general'
            },
            'total_preguntas' => $data['total_questions'] ?? 40,
            'puntaje_maximo' => $data['max_score'],
            'fecha_aplicacion' => $data['assessment_date'],
            'estado' => 'borrador', // Estado inicial
        ]);
    }
}
