<?php

namespace App\Modules\Psicologia\Application\UseCases;

use App\Modules\Psicologia\Infrastructure\Models\AtencionPsicologica;

class CreatePsychologyCare
{
    public function execute(array $data, string $psicologaId): AtencionPsicologica
    {
        return AtencionPsicologica::create([
            'alumno_id' => $data['student_id'],
            'psicologa_id' => $psicologaId,
            'incidencia_id' => $data['incident_id'] ?? null,
            'fecha_atencion' => $data['occurred_at'],
            'summary' => $data['summary'],
            'notas_privadas' => $data['confidential_notes'] ?? null,
        ]);
    }
}
