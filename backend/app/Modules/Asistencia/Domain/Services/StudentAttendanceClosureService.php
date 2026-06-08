<?php

namespace App\Modules\Asistencia\Domain\Services;

use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Modules\Asistencia\Domain\Models\AnomaliaAsistencia;
use App\Modules\Asistencia\Domain\Models\AsistenciaAlumno;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentAttendanceClosureService
{
    public function close(Carbon $date, User $actor): array
    {
        return DB::transaction(function () use ($date, $actor): array {
            $activeStudentIds = Matricula::query()
                ->where('estado', 'activo')
                ->pluck('alumno_id')
                ->unique();

            $createdAbsences = 0;
            $createdAnomalies = 0;

            foreach ($activeStudentIds as $studentId) {
                $attendance = AsistenciaAlumno::firstOrCreate([
                    'alumno_id' => $studentId,
                    'fecha' => $date->toDateString(),
                ], [
                    'estado' => 'falta_injustificada',
                    'presencia_abierta' => false,
                    'registrado_por' => $actor->id,
                ]);

                if ($attendance->wasRecentlyCreated) {
                    $createdAbsences++;

                    continue;
                }

                if ($attendance->primer_ingreso !== null && $attendance->estado === 'falta_injustificada') {
                    $attendance->update(['estado' => $attendance->primer_ingreso > '07:45:00' ? 'tardanza' : 'presente']);
                }

                if ($attendance->presencia_abierta) {
                    $exists = AnomaliaAsistencia::where('asistencia_alumno_id', $attendance->id)
                        ->where('tipo', 'sin_salida')
                        ->where('estado', 'pendiente')
                        ->exists();

                    if (! $exists) {
                        AnomaliaAsistencia::create([
                            'asistencia_alumno_id' => $attendance->id,
                            'tipo' => 'sin_salida',
                            'estado' => 'pendiente',
                            'detalle' => 'Ingreso sin salida registrada al cierre de jornada.',
                            'asignado_a' => $actor->id,
                        ]);
                        $createdAnomalies++;
                    }
                }
            }

            return ['absences_created' => $createdAbsences, 'anomalies_created' => $createdAnomalies];
        });
    }

    public function unjustifiedAbsenceAlerts(int $threshold = 3): array
    {
        return AsistenciaAlumno::query()
            ->selectRaw('alumno_id, count(*) as unjustified_absences')
            ->where('estado', 'falta_injustificada')
            ->groupBy('alumno_id')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->with('alumno')
            ->get()
            ->map(fn (AsistenciaAlumno $row): array => [
                'student_id' => $row->alumno_id,
                'student_name' => trim(($row->alumno?->nombres ?? '').' '.($row->alumno?->apellidos ?? '')),
                'unjustified_absences' => (int) $row->unjustified_absences,
            ])
            ->values()
            ->all();
    }
}
