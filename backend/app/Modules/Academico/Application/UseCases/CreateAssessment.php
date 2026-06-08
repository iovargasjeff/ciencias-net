<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Examen;

class CreateAssessment
{
    public function execute(array $data): Examen
    {
        return Examen::create([
            'carga_academica_id' => $data['teaching_assignment_id'],
            'titulo' => $data['title'],
            'assessment_type' => $data['assessment_type'],
            'channel' => $data['channel'] ?? 'general',
            'total_preguntas' => $data['total_questions'] ?? 40,
            'puntaje_maximo' => $data['max_score'],
            'fecha_aplicacion' => $data['assessment_date'],
            'estado' => 'borrador',
        ]);
    }
}
