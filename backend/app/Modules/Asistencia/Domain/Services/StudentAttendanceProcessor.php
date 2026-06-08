<?php

namespace App\Modules\Asistencia\Domain\Services;

use App\Modules\Asistencia\Domain\Models\AsistenciaAlumno;
use App\Modules\Asistencia\Domain\Models\CamaraEstacion;
use App\Modules\Asistencia\Domain\Models\ConfiguracionJornada;
use App\Modules\Asistencia\Domain\Models\EventoReconocimiento;
use App\Modules\Asistencia\Domain\Models\MovimientoAsistencia;
use App\Modules\Asistencia\Infrastructure\Notifications\StudentAttendanceMovementNotification;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class StudentAttendanceProcessor
{
    public function processFacialEvent(EventoReconocimiento $event, Alumno $student, CamaraEstacion $camera): MovimientoAsistencia
    {
        return DB::transaction(function () use ($event, $student, $camera): MovimientoAsistencia {
            $occurredAt = Carbon::parse($event->capturado_en);
            $type = $this->resolveType($student, $occurredAt, $camera->modo);
            $technicalCreator = User::find($event->cuentaTecnica?->creado_por);
            $attendance = $this->attendanceFor($student, $occurredAt, $technicalCreator);
            $movement = $this->createMovement($attendance, $type, 'regular', null, $occurredAt, 'facial', null, $event->cuenta_tecnica_id, $event->estacion_id, $event->camara_estacion_id, $event->id, $event->confianza);
            $this->updateSummary($attendance, $movement);
            $event->update([
                'user_id' => $student->user_id,
                'tipo_persona' => 'alumno',
                'tipo_evento_resuelto' => $type,
                'estado' => 'aceptado',
                'motivo_estado' => null,
            ]);
            $this->notifyParents($student, $movement);

            return $movement;
        });
    }

    public function processManualEvent(Alumno $student, string $eventType, Carbon $occurredAt, string $reason, User $actor): MovimientoAsistencia
    {
        return DB::transaction(function () use ($student, $eventType, $occurredAt, $reason, $actor): MovimientoAsistencia {
            $attendance = $this->attendanceFor($student, $occurredAt, $actor);
            $type = match ($eventType) {
                'entry', 'late' => 'ingreso',
                'exit', 'absence' => 'salida',
            };
            $reasonType = str_contains(mb_strtolower($reason), 'emergencia') ? 'emergencia' : 'otro';
            $movement = $this->createMovement($attendance, $type, $reasonType, $reason, $occurredAt, 'manual', $actor->id, null);
            $this->updateSummary($attendance, $movement, $eventType === 'late');
            $this->notifyParents($student, $movement);

            return $movement;
        });
    }

    private function attendanceFor(Alumno $student, Carbon $occurredAt, ?User $actor): AsistenciaAlumno
    {
        return AsistenciaAlumno::firstOrCreate([
            'alumno_id' => $student->id,
            'fecha' => $occurredAt->toDateString(),
        ], [
            'estado' => $this->isLate($student, $occurredAt) ? 'tardanza' : 'presente',
            'presencia_abierta' => false,
            'registrado_por' => $actor?->id ?? User::query()->oldest()->value('id'),
        ]);
    }

    private function resolveType(Alumno $student, Carbon $occurredAt, string $cameraMode): string
    {
        return match ($cameraMode) {
            'entrada' => 'ingreso',
            'salida' => 'salida',
            default => $this->nextBidirectionalType($student, $occurredAt),
        };
    }

    private function nextBidirectionalType(Alumno $student, Carbon $occurredAt): string
    {
        $last = MovimientoAsistencia::query()
            ->whereHas('asistenciaAlumno', fn ($query) => $query->where('alumno_id', $student->id)->whereDate('fecha', $occurredAt->toDateString()))
            ->latest('ocurrido_en')
            ->first();

        if ($last === null) {
            return 'ingreso';
        }

        return $last->tipo === 'salida' ? 'reingreso' : 'salida';
    }

    private function createMovement(
        AsistenciaAlumno $attendance,
        string $type,
        string $reason,
        ?string $observation,
        Carbon $occurredAt,
        string $origin,
        ?string $registeredBy,
        ?string $technicalAccountId,
        ?string $stationId = null,
        ?string $cameraId = null,
        ?string $recognitionEventId = null,
        ?string $confidence = null,
    ): MovimientoAsistencia {
        return MovimientoAsistencia::create([
            'asistencia_alumno_id' => $attendance->id,
            'tipo' => $type,
            'motivo' => $reason,
            'observacion' => $observation,
            'ocurrido_en' => $occurredAt,
            'origen' => $origin,
            'estacion_id' => $stationId,
            'camara_estacion_id' => $cameraId,
            'evento_reconocimiento_id' => $recognitionEventId,
            'confianza_reconocimiento' => $confidence,
            'notificacion_enviada' => false,
            'registrado_por' => $registeredBy,
            'cuenta_tecnica_id' => $technicalAccountId,
        ]);
    }

    private function updateSummary(AsistenciaAlumno $attendance, MovimientoAsistencia $movement, bool $forceLate = false): void
    {
        $updates = ['presencia_abierta' => in_array($movement->tipo, ['ingreso', 'reingreso'], true)];
        if (in_array($movement->tipo, ['ingreso', 'reingreso'], true) && $attendance->primer_ingreso === null) {
            $updates['primer_ingreso'] = Carbon::parse($movement->ocurrido_en)->format('H:i:s');
            $updates['estado'] = ($forceLate || $this->isLate($attendance->alumno, Carbon::parse($movement->ocurrido_en))) ? 'tardanza' : 'presente';
        }
        if ($movement->tipo === 'salida') {
            $updates['ultima_salida'] = Carbon::parse($movement->ocurrido_en)->format('H:i:s');
        }
        $attendance->update($updates);
    }

    private function isLate(Alumno $student, Carbon $occurredAt): bool
    {
        $limit = ConfiguracionJornada::query()
            ->where('activo', true)
            ->where('dia_semana', (int) $occurredAt->isoWeekday())
            ->orderByRaw('grado_id IS NULL')
            ->value('hora_limite_puntual') ?? '07:45:00';

        return $occurredAt->format('H:i:s') > $limit;
    }

    private function notifyParents(Alumno $student, MovimientoAsistencia $movement): void
    {
        $parents = $student->padres()->with('user')->wherePivot('recibe_notificaciones', true)->get()->pluck('user')->filter();
        if ($parents->isNotEmpty()) {
            Notification::send($parents, new StudentAttendanceMovementNotification($student, $movement));
        }
        $movement->update(['notificacion_enviada' => $parents->isNotEmpty()]);
    }
}
