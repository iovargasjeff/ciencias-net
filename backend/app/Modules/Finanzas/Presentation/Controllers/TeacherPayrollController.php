<?php

namespace App\Modules\Finanzas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Modules\Finanzas\Domain\Models\LiquidacionDescuentoDocente;
use App\Modules\Finanzas\Domain\Models\TarifaDocente;
use App\Modules\Finanzas\Domain\Services\TeacherPayrollLiquidationService;
use App\Modules\Finanzas\Presentation\Requests\TeacherPayroll\CreatePayrollLiquidationRequest;
use App\Modules\Finanzas\Presentation\Requests\TeacherPayroll\CreateTeacherRateRequest;
use App\Modules\Finanzas\Presentation\Requests\TeacherPayroll\GenerateTeacherPayrollReportRequest;
use App\Modules\Finanzas\Presentation\Resources\PayrollLiquidationResource;
use App\Modules\Finanzas\Presentation\Resources\TeacherRateResource;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeacherPayrollController extends Controller
{
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
}
