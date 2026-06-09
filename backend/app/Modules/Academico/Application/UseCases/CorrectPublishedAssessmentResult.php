<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Nota;
use Illuminate\Support\Facades\DB;

class CorrectPublishedAssessmentResult
{
    public function __construct(private PublishAssessment $publishAssessment) {}

    public function execute(Nota $nota, float $score, string $reason): void
    {
        DB::transaction(function () use ($nota, $score, $reason) {
            $nota->update([
                'puntaje' => $score,
                'observacion' => $nota->observacion ? $nota->observacion."\nCorrección: ".$reason : 'Corrección: '.$reason,
            ]);

            // Recalcular el ranking para todo el examen
            $this->publishAssessment->execute($nota->examen);

            // TODO: Registrar auditoría y enviar notificación de corrección al alumno/padre
        });
    }
}
