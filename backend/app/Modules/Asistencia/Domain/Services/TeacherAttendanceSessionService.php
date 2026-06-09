<?php

namespace App\Modules\Asistencia\Domain\Services;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Asistencia\Domain\Models\AsistenciaDocente;
use App\Modules\Asistencia\Domain\Models\MovimientoAsistencia;
use App\Modules\Asistencia\Domain\Models\SesionClase;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceSessionService
{
    public function createSessionFromAssignment(CargaAcademica $assignment, Carbon $date, string $start, string $end): SesionClase
    {
        return SesionClase::firstOrCreate([
            'carga_academica_id' => $assignment->id,
            'fecha' => $date->toDateString(),
            'hora_inicio' => $start,
        ], [
            'hora_fin' => $end,
            'estado' => 'programada',
        ]);
    }

    public function registerEntry(Docente $teacher, Carbon $occurredAt, User $actor): AsistenciaDocente
    {
        return DB::transaction(function () use ($teacher, $occurredAt, $actor): AsistenciaDocente {
            $attendance = AsistenciaDocente::firstOrCreate([
                'docente_id' => $teacher->id,
                'fecha' => $occurredAt->toDateString(),
            ], [
                'estado' => 'presente',
                'minutos_tardanza' => 0,
                'registrado_por' => $actor->id,
            ]);

            $firstSession = $this->firstSessionForTeacher($teacher, $occurredAt->toDateString());
            $lateMinutes = 0;
            if ($firstSession !== null) {
                $start = Carbon::parse($occurredAt->toDateString().' '.$firstSession->hora_inicio);
                $lateMinutes = max(0, $start->diffInMinutes($occurredAt, false));
            }

            $attendance->update([
                'estado' => 'presente',
                'primer_ingreso' => $attendance->primer_ingreso ?? $occurredAt->format('H:i:s'),
                'minutos_tardanza' => max($attendance->minutos_tardanza, $lateMinutes),
            ]);

            MovimientoAsistencia::create([
                'asistencia_docente_id' => $attendance->id,
                'tipo' => 'ingreso',
                'motivo' => 'regular',
                'ocurrido_en' => $occurredAt,
                'origen' => 'manual',
                'registrado_por' => $actor->id,
            ]);

            return $attendance->refresh();
        });
    }

    public function closeEndedSessions(Carbon $now, User $actor): array
    {
        return DB::transaction(function () use ($now, $actor): array {
            $absences = 0;
            $sessions = SesionClase::query()
                ->with('cargaAcademica.docente')
                ->where('estado', 'programada')
                ->whereDate('fecha', '<=', $now->toDateString())
                ->get()
                ->filter(fn (SesionClase $session): bool => Carbon::parse($session->fecha->toDateString().' '.$session->hora_fin)->lte($now));

            foreach ($sessions as $session) {
                $teacher = $session->docente_sustituto_id ?? $session->cargaAcademica->docente_id;
                $attendance = AsistenciaDocente::firstOrCreate([
                    'docente_id' => $teacher,
                    'fecha' => $session->fecha->toDateString(),
                ], [
                    'estado' => 'falta_injustificada',
                    'minutos_tardanza' => 0,
                    'docente_sustituto_id' => $session->docente_sustituto_id,
                    'registrado_por' => $actor->id,
                ]);

                if ($attendance->wasRecentlyCreated) {
                    $session->update(['estado' => 'docente_ausente', 'revisado_planilla_por' => $actor->id]);
                    $absences++;
                } else {
                    $session->update(['estado' => 'realizada', 'revisado_planilla_por' => $actor->id]);
                }
            }

            return ['sessions_reviewed' => $sessions->count(), 'teacher_absences_created' => $absences];
        });
    }

    public function applyMinutesAdjustment(Docente $teacher, Carbon $date, string $type, int $minutes, string $reason, User $actor): AsistenciaDocente
    {
        return DB::transaction(function () use ($teacher, $date, $type, $minutes, $reason, $actor): AsistenciaDocente {
            $attendance = AsistenciaDocente::firstOrCreate([
                'docente_id' => $teacher->id,
                'fecha' => $date->toDateString(),
            ], [
                'estado' => 'presente',
                'minutos_tardanza' => 0,
                'registrado_por' => $actor->id,
            ]);

            $nextMinutes = $type === 'add'
                ? $attendance->minutos_tardanza + $minutes
                : max(0, $attendance->minutos_tardanza - $minutes);

            $attendance->update(['estado' => 'presente', 'minutos_tardanza' => $nextMinutes]);
            MovimientoAsistencia::create([
                'asistencia_docente_id' => $attendance->id,
                'tipo' => 'ingreso',
                'motivo' => 'otro',
                'observacion' => $reason,
                'ocurrido_en' => $date->copy()->startOfDay(),
                'origen' => 'manual',
                'registrado_por' => $actor->id,
            ]);

            return $attendance->refresh();
        });
    }

    private function firstSessionForTeacher(Docente $teacher, string $date): ?SesionClase
    {
        return SesionClase::query()
            ->whereDate('fecha', $date)
            ->where(function ($query) use ($teacher): void {
                $query->where('docente_sustituto_id', $teacher->id)
                    ->orWhereHas('cargaAcademica', fn ($assignment) => $assignment->where('docente_id', $teacher->id));
            })
            ->where('estado', '!=', 'cancelada')
            ->orderBy('hora_inicio')
            ->first();
    }
}
