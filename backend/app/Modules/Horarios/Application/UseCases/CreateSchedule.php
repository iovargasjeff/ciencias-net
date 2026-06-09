<?php

namespace App\Modules\Horarios\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Horarios\Infrastructure\Models\Horario;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateSchedule
{
    public function execute(string $cargaAcademicaId, int $weekday, string $startsAt, string $endsAt, ?string $room): Horario
    {
        $carga = CargaAcademica::findOrFail($cargaAcademicaId);

        // Validar que la hora final es después de la inicial
        if (strtotime($endsAt) <= strtotime($startsAt)) {
            throw new \InvalidArgumentException('ends_at must be after starts_at');
        }

        // Detectar cruce de horario para LA MISMA SECCIÓN (mismos alumnos)
        $cruceSeccion = Horario::whereHas('cargaAcademica', function ($q) use ($carga) {
            $q->where('seccion_id', $carga->seccion_id);
        })
            ->where('dia_semana', $weekday)
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where(function ($q2) use ($startsAt, $endsAt) {
                    $q2->where('hora_inicio', '<', $endsAt)
                        ->where('hora_fin', '>', $startsAt);
                });
            })
            ->exists();

        if ($cruceSeccion) {
            throw new ConflictHttpException('La sección ya tiene clases programadas en ese horario.');
        }

        // Detectar cruce de horario para EL MISMO DOCENTE
        $cruceDocente = Horario::whereHas('cargaAcademica', function ($q) use ($carga) {
            $q->where('docente_id', $carga->docente_id);
        })
            ->where('dia_semana', $weekday)
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where(function ($q2) use ($startsAt, $endsAt) {
                    $q2->where('hora_inicio', '<', $endsAt)
                        ->where('hora_fin', '>', $startsAt);
                });
            })
            ->exists();

        if ($cruceDocente) {
            throw new ConflictHttpException('El docente ya tiene clases programadas en ese horario.');
        }

        return Horario::create([
            'carga_academica_id' => $cargaAcademicaId,
            'dia_semana' => $weekday,
            'hora_inicio' => $startsAt,
            'hora_fin' => $endsAt,
            'aula' => $room,
        ]);
    }
}
