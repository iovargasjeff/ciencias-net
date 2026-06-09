<?php

namespace App\Modules\Finanzas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Application\FinanceConfigurationService;
use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Presentation\Requests\CreatePaymentConceptRequest;
use App\Modules\Finanzas\Presentation\Requests\CreateStudentBenefitRequest;
use App\Modules\Finanzas\Presentation\Requests\ReasonRequest;
use App\Modules\Finanzas\Presentation\Requests\UpdatePaymentConceptRequest;
use App\Modules\Finanzas\Presentation\Resources\PaymentConceptResource;
use App\Modules\Finanzas\Presentation\Resources\StudentBenefitResource;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceConfigController extends Controller
{
    public function concepts(Request $request)
    {
        abort_unless($request->user()?->can('gestionar_finanzas') === true, 403);

        $query = ConceptoPago::query()
            ->when($request->filled('academic_period_id'), fn ($q) => $q->where('periodo_academico_id', $request->string('academic_period_id')->toString()))
            ->when($request->filled('type'), fn ($q) => $q->where('tipo', $request->string('type')->toString()))
            ->latest('created_at');

        return PaymentConceptResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storeConcept(CreatePaymentConceptRequest $request, FinanceConfigurationService $service, AuditLogger $audit): JsonResponse
    {
        $concept = $service->createConcept($request->validated(), $request->user());
        $audit->record($request, 'finance.concept_created', $request->user(), $concept);

        return response()->json(['data' => new PaymentConceptResource($concept)], 201);
    }

    public function updateConcept(string $conceptId, UpdatePaymentConceptRequest $request, FinanceConfigurationService $service, AuditLogger $audit): JsonResponse
    {
        $concept = ConceptoPago::findOrFail($conceptId);
        $old = $concept->toArray();
        $updated = $service->updateConcept($concept, $request->validated(), $request->user());
        $audit->record($request, 'finance.concept_updated', $request->user(), $updated, $old, $updated->toArray());

        return response()->json(['data' => new PaymentConceptResource($updated)]);
    }

    public function benefits(Request $request)
    {
        abort_unless($request->user()?->can('gestionar_finanzas') === true, 403);

        $query = BeneficioAlumno::query()
            ->with('conceptos')
            ->when($request->filled('student_id'), fn ($q) => $q->where('alumno_id', $request->string('student_id')->toString()))
            ->when($request->has('active'), fn ($q) => $q->where('activo', $request->boolean('active')))
            ->latest('created_at');

        return StudentBenefitResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storeBenefit(CreateStudentBenefitRequest $request, FinanceConfigurationService $service, AuditLogger $audit): JsonResponse
    {
        $benefit = $service->createBenefit($request->validated(), $request->user());
        $audit->record($request, 'finance.benefit_created', $request->user(), $benefit);

        return response()->json(['data' => new StudentBenefitResource($benefit)], 201);
    }

    public function deactivateBenefit(string $benefitId, ReasonRequest $request, FinanceConfigurationService $service, AuditLogger $audit): JsonResponse
    {
        $benefit = BeneficioAlumno::with('conceptos')->findOrFail($benefitId);
        $old = $benefit->toArray();
        $updated = $service->deactivateBenefit($benefit, $request->string('reason')->toString());
        $audit->record($request, 'finance.benefit_deactivated', $request->user(), $updated, $old, $updated->toArray());

        return response()->json(['data' => new StudentBenefitResource($updated)]);
    }
}
