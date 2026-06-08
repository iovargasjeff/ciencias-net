<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Nota;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarNotaUseCase
{
    public function execute(Nota $nota, array $data, User $user): Nota
    {
        $this->validateData($nota, $data);

        return DB::transaction(function () use ($nota, $data, $user) {
            $estado = $data['estado'] ?? $nota->estado;
            $nuevoPuntaje = null;

            if ($estado === 'registrada') {
                $nuevoPuntaje = $data['puntaje'] ?? $nota->puntaje;
            }

            $oldPuntaje = $nota->puntaje;
            $oldEstado = $nota->estado;

            $nota->update([
                'puntaje' => $nuevoPuntaje,
                'estado' => $estado,
                'observacion' => $data['observacion'] ?? $nota->observacion,
            ]);

            // Registrar en audit_logs si cambió algo fundamental
            if ($oldPuntaje !== $nuevoPuntaje || $oldEstado !== $estado) {
                DB::table('audit_logs')->insert([
                    'user_id' => $user->id,
                    'action' => 'UPDATE_NOTA',
                    'model' => 'Nota',
                    'model_id' => $nota->id,
                    'old_values' => json_encode(['puntaje' => $oldPuntaje, 'estado' => $oldEstado]),
                    'new_values' => json_encode(['puntaje' => $nuevoPuntaje, 'estado' => $estado]),
                    'ip' => request()->ip() ?? '127.0.0.1',
                    'created_at' => now(),
                ]);
            }

            return $nota->refresh();
        });
    }

    private function validateData(Nota $nota, array $data): void
    {
        $estado = $data['estado'] ?? $nota->estado;
        $puntaje = $data['puntaje'] ?? $nota->puntaje;

        $examen = $nota->examen;

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
            if ($puntaje !== null && $puntaje != 0) {
                throw ValidationException::withMessages([
                    'puntaje' => ["El puntaje debe ser vacío o 0 si el estado es {$estado}."],
                ]);
            }
        }
    }
}
