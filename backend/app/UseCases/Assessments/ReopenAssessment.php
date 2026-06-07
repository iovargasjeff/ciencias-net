<?php

namespace App\UseCases\Assessments;

use App\Models\Examen;
use Illuminate\Support\Facades\Log;

class ReopenAssessment
{
    public function execute(Examen $examen, string $userId): Examen
    {
        if ($examen->estado !== 'cerrado') {
            return $examen;
        }

        $oldStatus = $examen->estado;
        // Si estaba publicado antes de cerrar, quizás vuelva a publicado, pero por defecto a borrador o listo
        // Asumimos 'publicado' si ya tiene publicado_en, o 'listo'
        $newStatus = $examen->publicado_en ? 'publicado' : 'borrador';

        $examen->update(['estado' => $newStatus]);

        Log::info('Evaluacion reabierta', [
            'examen_id' => $examen->id,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return $examen;
    }
}
