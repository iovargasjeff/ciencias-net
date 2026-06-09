<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Examen;
use Illuminate\Support\Facades\DB;

class PublishAssessment
{
    public function execute(Examen $examen): void
    {
        DB::transaction(function () use ($examen) {
            // Obtener todas las notas registradas para este examen
            $notas = $examen->notas()->where('estado', 'registrada')->orderByDesc('puntaje')->get();

            $puesto = 1;
            $puntajeAnterior = null;
            $mismoPuestoCount = 0;

            foreach ($notas as $nota) {
                if ($puntajeAnterior !== null && $nota->puntaje < $puntajeAnterior) {
                    $puesto += $mismoPuestoCount;
                    $mismoPuestoCount = 1;
                } else {
                    $mismoPuestoCount++;
                }

                $nota->update(['puesto_ranking' => $puesto]);
                $puntajeAnterior = $nota->puntaje;
            }

            // Excluir ranking para los que tienen estado ausente o exonerado
            $examen->notas()->whereIn('estado', ['ausente', 'exonerado'])->update(['puesto_ranking' => null]);

            $examen->update([
                'estado' => 'publicado',
                'publicado_por' => auth()->id(),
                'publicado_en' => now(),
            ]);

            // TODO: Enviar notificaciones a los alumnos/padres
        });
    }
}
