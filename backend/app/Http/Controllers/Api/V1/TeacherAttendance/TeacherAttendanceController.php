<?php

namespace App\Http\Controllers\Api\V1\TeacherAttendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherAttendance\AssignClassSessionSubstituteRequest;
use App\Http\Requests\TeacherAttendance\CancelClassSessionRequest;
use App\Http\Requests\TeacherAttendance\CreatePayrollLiquidationRequest;
use App\Http\Requests\TeacherAttendance\CreateTeacherAttendanceAdjustmentRequest;
use App\Http\Requests\TeacherAttendance\CreateTeacherRateRequest;
use App\Http\Requests\TeacherAttendance\GenerateTeacherPayrollReportRequest;
use App\Http\Resources\ClassSessionResource;
use App\Http\Resources\PayrollLiquidationResource;
use App\Http\Resources\TeacherAttendanceResource;
use App\Http\Resources\TeacherRateResource;
use App\Models\AsistenciaDocente;
use App\Models\Docente;
use App\Models\LiquidacionDescuentoDocente;
use App\Models\SesionClase;
use App\Models\TarifaDocente;
use App\Support\Attendance\TeacherAttendanceSessionService;
use App\Support\Attendance\TeacherPayrollLiquidationService;
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

    public function rates(Request $request)
    {
        abort_unless($request->user()?->can('gestionar_planilla') === true, 403);

        return TeacherRateResource::collection(
            TarifaDocente::query()->latest('vigente_desde')->paginate(min($request->integer('per_page', 20), 100))
        );
    }

    public function storeRate(CreateTeacherRateRequest $request, TeacherPayrollLiquidationService $service, AuditLogger $audit): JsonResponse
    {
        $rate = $service->createRate(
            Docente::findOrFail($request->string('teacher_id')),
            $request->string('hourly_rate')->toString(),
            Carbon::parse($request->date('effective_from')),
            $request->filled('effective_until') ? Carbon::parse($request->date('effective_until')) : null,
            $request->user(),
        );
        $audit->record($request, 'teacher_payroll.rate_created', $request->user(), $rate);

        return response()->json(['data' => new TeacherRateResource($rate)], 201);
    }

    public function liquidations(Request $request)
    {
        abort_unless($request->user()?->can('gestionar_planilla') === true, 403);

        return PayrollLiquidationResource::collection(
            LiquidacionDescuentoDocente::query()->latest('periodo_anio')->latest('periodo_mes')->paginate(min($request->integer('per_page', 20), 100))
        );
    }

    public function storeLiquidation(CreatePayrollLiquidationRequest $request, TeacherPayrollLiquidationService $service, AuditLogger $audit): JsonResponse
    {
        $liquidations = $service->createMonthly(
            Carbon::parse($request->date('period_start')),
            Carbon::parse($request->date('period_end')),
            $request->array('teacher_ids'),
            $request->user(),
        );
        $audit->record($request, 'teacher_payroll.liquidation_created', $request->user(), subject: $request->date('period_start')->format('Y-m'), newValues: ['count' => count($liquidations)]);

        return response()->json(['data' => PayrollLiquidationResource::collection(collect($liquidations))->resolve()], 201);
    }

    public function closeLiquidation(string $liquidationId, Request $request, TeacherPayrollLiquidationService $service, AuditLogger $audit): JsonResponse
    {
        abort_unless($request->user()?->can('cerrar_liquidacion') === true, 403);

        $liquidation = $service->close(LiquidacionDescuentoDocente::findOrFail($liquidationId), $request->user());
        $audit->record($request, 'teacher_payroll.liquidation_closed', $request->user(), $liquidation);

        return response()->json(['data' => new PayrollLiquidationResource($liquidation)]);
    }

    public function report(GenerateTeacherPayrollReportRequest $request, AuditLogger $audit): JsonResponse
    {
        $audit->record($request, 'teacher_payroll.report_requested', $request->user(), subject: $request->date('period_start')->format('Y-m'), newValues: [
            'format' => $request->string('format')->toString(),
            'teacher_count' => count($request->array('teacher_ids')),
        ]);

        return response()->json(['data' => [
            'status' => 'queued',
            'format' => $request->string('format')->toString(),
            'period_start' => $request->date('period_start')->toDateString(),
            'period_end' => $request->date('period_end')->toDateString(),
        ]], 202);
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
