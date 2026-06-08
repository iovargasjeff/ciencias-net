<?php

namespace App\Modules\Asistencia\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Asistencia\Domain\Models\AnomaliaAsistencia;
use App\Modules\Asistencia\Domain\Models\AsistenciaAlumno;
use App\Modules\Asistencia\Domain\Models\CamaraEstacion;
use App\Modules\Asistencia\Domain\Models\EventoReconocimiento;
use App\Modules\Asistencia\Domain\Services\StudentAttendanceProcessor;
use App\Modules\Asistencia\Infrastructure\Jobs\CloseStudentAttendanceDayJob;
use App\Modules\Asistencia\Infrastructure\Jobs\GenerateStudentAttendanceAlertsJob;
use App\Modules\Asistencia\Presentation\Requests\StudentAttendance\CloseStudentAttendanceDayRequest;
use App\Modules\Asistencia\Presentation\Requests\StudentAttendance\CreateManualStudentAttendanceEventRequest;
use App\Modules\Asistencia\Presentation\Requests\StudentAttendance\ReasonRequest;
use App\Modules\Asistencia\Presentation\Requests\StudentAttendance\ReviewRecognitionEventRequest;
use App\Modules\Asistencia\Presentation\Resources\RecognitionEventResource;
use App\Modules\Asistencia\Presentation\Resources\StudentAttendanceAnomalyResource;
use App\Modules\Asistencia\Presentation\Resources\StudentAttendanceMovementResource;
use App\Modules\Asistencia\Presentation\Resources\StudentAttendanceResource;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AsistenciaAlumno::query()->with(['movimientos', 'alumno.padres'])->latest('fecha');
        $user = $request->user();

        if ($user?->hasAnyRole(['superadmin', 'auxiliar', 'toe']) !== true) {
            if ($user?->padre !== null) {
                $studentIds = $user->padre->alumnos()->pluck('alumnos.id');
                $query->whereIn('alumno_id', $studentIds);
            } elseif ($user?->alumno !== null) {
                $query->where('alumno_id', $user->alumno->id);
            } else {
                abort(403);
            }
        }

        $query->when($request->filled('student_id'), fn ($q) => $q->where('alumno_id', $request->string('student_id')));

        return StudentAttendanceResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function manual(
        CreateManualStudentAttendanceEventRequest $request,
        StudentAttendanceProcessor $processor,
        AuditLogger $audit,
    ): JsonResponse {
        $student = Alumno::findOrFail($request->string('student_id'));
        $movement = $processor->processManualEvent(
            $student,
            $request->string('event_type')->toString(),
            Carbon::parse($request->date('occurred_at')),
            $request->string('reason')->toString(),
            $request->user(),
        );

        $audit->record($request, 'student_attendance.manual_event_created', $request->user(), $movement, newValues: [
            'student_id' => $student->id,
            'event_type' => $request->string('event_type')->toString(),
        ]);

        return response()->json(['data' => new StudentAttendanceMovementResource($movement)], 201);
    }

    public function closeDay(CloseStudentAttendanceDayRequest $request, AuditLogger $audit): JsonResponse
    {
        $result = app()->call([new CloseStudentAttendanceDayJob((string) $request->date('date'), $request->user()->id), 'handle']);
        $audit->record($request, 'student_attendance.day_closed', $request->user(), subject: (string) $request->date('date'), newValues: $result);

        return response()->json(['data' => ['status' => 'queued', ...$result]], 202);
    }

    public function anomalies(Request $request)
    {
        abort_unless($request->user()?->hasAnyRole(['superadmin', 'auxiliar']) === true, 403);

        return StudentAttendanceAnomalyResource::collection(
            AnomaliaAsistencia::query()->whereNotNull('asistencia_alumno_id')->latest('created_at')->paginate(min($request->integer('per_page', 20), 100))
        );
    }

    public function resolveAnomaly(ReasonRequest $request, string $anomalyId, AuditLogger $audit): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['superadmin', 'auxiliar']) === true, 403);

        $anomaly = AnomaliaAsistencia::whereNotNull('asistencia_alumno_id')->findOrFail($anomalyId);
        if ($anomaly->estado !== 'pendiente') {
            throw new ConflictHttpException('La anomalía ya fue resuelta.');
        }

        $anomaly->update([
            'estado' => 'resuelta',
            'resuelto_por' => $request->user()->id,
            'resolucion' => $request->string('reason')->toString(),
            'resuelto_en' => now(),
        ]);
        $audit->record($request, 'student_attendance.anomaly_resolved', $request->user(), $anomaly);

        return response()->json(['data' => new StudentAttendanceAnomalyResource($anomaly)]);
    }

    public function justifyAbsence(ReasonRequest $request, string $attendanceId, AuditLogger $audit): JsonResponse
    {
        $attendance = AsistenciaAlumno::findOrFail($attendanceId);
        if ($attendance->estado !== 'falta_injustificada') {
            throw new ConflictHttpException('Solo se justifican faltas injustificadas.');
        }

        $attendance->update(['estado' => 'falta_justificada']);
        $audit->record($request, 'student_attendance.absence_justified', $request->user(), $attendance, newValues: ['reason' => 'redacted']);

        return response()->json(['data' => new StudentAttendanceResource($attendance)]);
    }

    public function recognitionEvents(Request $request)
    {
        abort_unless($request->user()?->hasAnyRole(['superadmin', 'auxiliar']) === true, 403);

        return RecognitionEventResource::collection(
            EventoReconocimiento::query()->where('estado', 'pendiente_revision')->latest('capturado_en')->paginate(min($request->integer('per_page', 20), 100))
        );
    }

    public function reviewRecognition(
        ReviewRecognitionEventRequest $request,
        string $recognitionEventId,
        StudentAttendanceProcessor $processor,
        AuditLogger $audit,
    ): JsonResponse {
        $event = EventoReconocimiento::with('camara')->findOrFail($recognitionEventId);
        if ($event->estado !== 'pendiente_revision') {
            throw new ConflictHttpException('El evento ya fue revisado.');
        }

        DB::transaction(function () use ($request, $event, $processor): void {
            if ($request->string('outcome')->toString() === 'rejected') {
                $event->update(['estado' => 'rechazado', 'motivo_estado' => $request->string('reason')->toString(), 'revisado_por' => $request->user()->id, 'revisado_en' => now()]);

                return;
            }

            $student = $request->filled('matched_student_id')
                ? Alumno::findOrFail($request->string('matched_student_id'))
                : Alumno::where('user_id', $event->user_id)->firstOrFail();
            $camera = $event->camara ?? CamaraEstacion::findOrFail($event->camara_estacion_id);
            $processor->processFacialEvent($event, $student, $camera);
            $event->update(['revisado_por' => $request->user()->id, 'revisado_en' => now()]);
        });

        $audit->record($request, 'student_attendance.recognition_reviewed', $request->user(), $event, newValues: ['outcome' => $request->string('outcome')->toString()]);

        return response()->json(['data' => new RecognitionEventResource($event->refresh())]);
    }

    public function alerts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['superadmin', 'auxiliar', 'toe']) === true, 403);

        return response()->json(['data' => dispatch_sync(new GenerateStudentAttendanceAlertsJob)]);
    }
}
