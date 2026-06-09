<?php

namespace App\Modules\Horarios\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Horarios\Infrastructure\Models\EventoCalendario;
use Illuminate\Support\Facades\Auth;

class CreateCalendarEvent
{
    public function execute(string $title, string $startsAt, string $endsAt, string $eventType, ?string $description): EventoCalendario
    {
        if (strtotime($endsAt) <= strtotime($startsAt)) {
            throw new \InvalidArgumentException('ends_at must be after starts_at');
        }

        // Mapeo del API (academic, holiday, meeting, other) a Base de Datos (evento, examen, simulacro, no_laboral)
        $mappedType = 'evento';
        if ($eventType === 'holiday') {
            $mappedType = 'no_laboral';
        }

        // Determinar el periodo activo (en este caso asumiremos que se crea en el último periodo activo para simplificar,
        // o si es a nivel institucional, el periodo activo tipo colegio. Para CienciasNet buscaremos el primero activo)
        $periodo = PeriodoAcademico::where('estado', 'activo')->first();
        if (! $periodo) {
            throw new \RuntimeException('No active academic period found.');
        }

        return EventoCalendario::create([
            'periodo_academico_id' => $periodo->id,
            'tipo' => $mappedType,
            'titulo' => $title,
            'descripcion' => $description,
            'fecha_inicio' => $startsAt,
            'fecha_fin' => $endsAt,
            'creado_por' => Auth::id(),
        ]);
    }
}
