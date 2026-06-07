<?php

namespace App\Http\Controllers\Api\V1\TeacherAttendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherAttendance\AssignClassSessionSubstituteRequest;
use App\Http\Requests\TeacherAttendance\CancelClassSessionRequest;
use App\Http\Requests\TeacherAttendance\CreateTeacherAttendanceAdjustmentRequest;
use App\Http\Resources\ClassSessionResource;
use App\Http\Resources\TeacherAttendanceResource;
use App\Models\AsistenciaDocente;
use App\Models\Docente;
use App\Models\SesionClase;
use App\Support\Attendance\TeacherAttendanceSessionService;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('gestionar_planilla') === true, 403);

        return TeacherAttendanceResource::collection(
            AsistenciaDocente::query()->latest('fecha')->paginate(min($request->integer('per_page', 20), 100))
        );
    }

    public function adjustment(CreateTeacherAttendanceAdjustmentRequest $request, TeacherAttendanceSessionService $service, AuditLogger $audit): JsonResponse
    {
        $teacher = Docente::findOrFail($request->string('teacher_id'));
        if (Docente::where('user_id', $request->user()->id)->whereKey($teacher->id)->exists()) {
            abort(403);
        }

        $attendance = $service->applyMinutesAdjustment(
            $teacher,
            Carbon::parse($request->date('date')),
            $request->string('adjustment_type')->toString(),
            $request->integer('minutes'),
            $request->string('reason')->toString(),
            $request->user(),
        );
        $audit->record($request, 'teacher_attendance.adjusted', $request->user(), $attendance, newValues: ['reason' => 'redacted']);

        return response()->json(['data' => new TeacherAttendanceResource($attendance)], 201);
    }

    public function cancel(CancelClassSessionRequest $request, string $classSessionId, AuditLogger $audit): JsonResponse
    {
        $session = SesionClase::findOrFail($classSessionId);
        if ($session->estado !== 'programada') {
            throw new ConflictHttpException('Solo se cancelan sesiones programadas.');
        }

        $session->update([
            'estado' => 'cancelada',
            'motivo_cancelacion' => $request->string('reason')->toString(),
            'cancelada_por' => $request->user()->id,
        ]);
        $audit->record($request, 'teacher_attendance.session_cancelled', $request->user(), $session, newValues: ['reason' => 'redacted']);

        return response()->json(['data' => new ClassSessionResource($session)]);
    }

    public function substitute(AssignClassSessionSubstituteRequest $request, string $classSessionId, AuditLogger $audit): JsonResponse
    {
        $session = SesionClase::findOrFail($classSessionId);
        if ($session->estado === 'cancelada') {
            throw new ConflictHttpException('No se asigna sustituto a sesiones canceladas.');
        }

        $session->update([
            'docente_sustituto_id' => $request->string('teacher_id')->toString(),
            'revisado_planilla_por' => $request->user()->id,
        ]);
        $audit->record($request, 'teacher_attendance.substitute_assigned', $request->user(), $session, newValues: ['teacher_id' => $request->string('teacher_id')->toString()]);

        return response()->json(['data' => new ClassSessionResource($session)]);
    }
}
