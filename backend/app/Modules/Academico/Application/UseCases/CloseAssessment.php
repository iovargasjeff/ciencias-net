<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Examen;
use Illuminate\Support\Facades\Log;

class CloseAssessment
{
    public function execute(Examen $examen, string $userId): Examen
    {
        if ($examen->estado === 'cerrado') {
            return $examen;
        }

        $oldStatus = $examen->estado;
        $examen->update(['estado' => 'cerrado']);

        Log::info('Evaluacion cerrada', [
            'examen_id' => $examen->id,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => 'cerrado',
        ]);

        return $examen;
    }
}
