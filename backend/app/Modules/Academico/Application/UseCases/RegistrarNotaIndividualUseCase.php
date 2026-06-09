<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Examen;
use App\Modules\Academico\Infrastructure\Models\Nota;
use Illuminate\Validation\ValidationException;

class RegistrarNotaIndividualUseCase
{
    public function execute(Examen $examen, array $data, string $userId): Nota
    {
        $this->validateData($examen, $data);

        // Prepare data
        $estado = $data['estado'];
        $puntaje = null;

        if ($estado === 'registrada') {
            $puntaje = $data['puntaje'] ?? 0;
        }

        return Nota::updateOrCreate(
            [
                'examen_id' => $examen->id,
                'matricula_id' => $data['matricula_id'],
            ],
            [
                'puntaje' => $puntaje,
                'estado' => $estado,
                'observacion' => $data['observacion'] ?? null,
                'registrado_por' => $userId,
            ]
        );
    }

    private function validateData(Examen $examen, array $data): void
    {
        $estado = $data['estado'];
        $puntaje = $data['puntaje'] ?? null;

        if ($estado === 'registrada') {
            if ($puntaje === null) {
                throw ValidationException::withMessages([
                    'puntaje' => ['El puntaje es requerido cuando el estado es registrada.'],
                ]);
            }

            if ($puntaje > $examen->puntaje_maximo) {
                throw ValidationException::withMessages([
                    'puntaje' => ["El puntaje ({$puntaje}) no puede superar el máximo del examen ({$examen->puntaje_maximo})."],
                ]);
            }
        } else {
            // Ausente, exonerado, pendiente -> puntaje must be null or 0
            if ($puntaje !== null && $puntaje != 0) {
                throw ValidationException::withMessages([
                    'puntaje' => ["El puntaje debe ser vacío o 0 si el estado es {$estado}."],
                ]);
            }
        }
    }
}
