<?php

namespace App\Modules\Finanzas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Application\DTOs\AdjustmentDTO;
use App\Modules\Finanzas\Application\DTOs\BulkAdjustmentDTO;
use App\Modules\Finanzas\Application\DTOs\GenerateObligationsDTO;
use App\Modules\Finanzas\Domain\Services\ObligationAdjustmentService;
use App\Modules\Finanzas\Domain\Services\ObligationGenerationService;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Finanzas\Infrastructure\Repositories\EloquentObligationRepository;
use App\Modules\Finanzas\Presentation\Requests\AdjustPaymentObligationRequest;
use App\Modules\Finanzas\Presentation\Requests\BulkAdjustPaymentObligationRequest;
use App\Modules\Finanzas\Presentation\Requests\GeneratePaymentObligationsRequest;
use App\Modules\Finanzas\Presentation\Requests\ListPaymentObligationsRequest;
use App\Modules\Finanzas\Presentation\Resources\BulkOperationResultResource;
use App\Modules\Finanzas\Presentation\Resources\PaymentObligationResource;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Controller for payment obligations operations.
 * Handles generation, listing, and adjustment of payment obligations.
 */
class PaymentObligationController extends Controller
{
    public function __construct(
        private ObligationGenerationService $generationService,
        private ObligationAdjustmentService $adjustmentService,
        private EloquentObligationRepository $repository,
        private AuditLogger $auditLogger
    ) {}

    /**
     * List payment obligations with filters.
     * GET /api/v1/payment-obligations
     *
     * @param  ListPaymentObligationsRequest  $request
     * @return ResourceCollection
     */
    public function index(ListPaymentObligationsRequest $request): ResourceCollection
    {
        $filters = $request->validated();
        $perPage = $filters['per_page'] ?? 20;
        unset($filters['per_page']);

        $paginated = $this->repository->paginated($filters, $perPage);

        return PaymentObligationResource::collection($paginated);
    }

    /**
     * Generate payment obligations.
     * POST /api/v1/payment-obligations
     *
     * Returns 202 Accepted with idempotency support.
     *
     * @param  GeneratePaymentObligationsRequest  $request
     * @return JsonResponse
     */
    public function store(GeneratePaymentObligationsRequest $request): JsonResponse
    {
        $dto = GenerateObligationsDTO::fromRequest($request->validated());

        try {
            $obligations = $this->generationService->generate(
                conceptId: $dto->conceptId,
                periodId: $dto->academicPeriodId,
                dueDate: $dto->dueDate,
                studentIds: $dto->studentIds,
                generatedBy: $request->user()
            );

            // Record in audit log
            $this->auditLogger->record(
                $request,
                'finance.obligations_generated',
                $request->user(),
                [
                    'concept_id' => $dto->conceptId,
                    'count' => $obligations->count(),
                    'student_ids' => $dto->studentIds,
                ]
            );

            return response()->json([
                'data' => PaymentObligationResource::collection($obligations),
                'meta' => [
                    'count' => $obligations->count(),
                ],
            ], 202);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'generation_failed',
            ], 409);
        }
    }

    /**
     * Get single obligation details.
     * GET /api/v1/payment-obligations/{obligationId}
     *
     * @param  string  $obligationId
     * @return PaymentObligationResource
     */
    public function show(string $obligationId): PaymentObligationResource
    {
        $obligation = $this->repository->findOrFail($obligationId);

        return new PaymentObligationResource($obligation);
    }

    /**
     * Adjust a pending obligation.
     * POST /api/v1/payment-obligations/{obligationId}/adjustments
     *
     * @param  string  $obligationId
     * @param  AdjustPaymentObligationRequest  $request
     * @return JsonResponse
     */
    public function adjust(string $obligationId, AdjustPaymentObligationRequest $request): JsonResponse
    {
        try {
            $obligation = $this->repository->findOrFail($obligationId);

            $dto = AdjustmentDTO::fromRequest($request->validated());

            $adjusted = $this->adjustmentService->adjust(
                obligation: $obligation,
                adjustmentData: [
                    'adjustment_type' => $dto->adjustmentType,
                    'amount' => $dto->amount,
                    'reason' => $dto->reason,
                ],
                adjustedBy: $request->user()
            );

            return response()->json([
                'data' => new PaymentObligationResource($adjusted),
                'message' => 'Obligación ajustada correctamente.',
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'adjustment_failed',
            ], 409);
        }
    }

    /**
     * Bulk adjust multiple obligations.
     * POST /api/v1/payment-obligations/bulk-adjustments
     *
     * Returns 202 Accepted for asynchronous processing.
     *
     * @param  BulkAdjustPaymentObligationRequest  $request
     * @return JsonResponse
     */
    public function bulkAdjust(BulkAdjustPaymentObligationRequest $request): JsonResponse
    {
        try {
            $dto = BulkAdjustmentDTO::fromRequest($request->validated());

            $count = $this->adjustmentService->bulkAdjust(
                filters: $dto->filters,
                adjustmentData: [
                    'adjustment_type' => $dto->adjustmentType,
                    'amount' => $dto->amount,
                    'reason' => $dto->reason,
                ],
                adjustedBy: $request->user()
            );

            // Record in audit log
            $this->auditLogger->record(
                $request,
                'finance.obligations_bulk_adjusted',
                $request->user(),
                [
                    'filters' => $dto->filters,
                    'count_affected' => $count,
                    'adjustment_type' => $dto->adjustmentType,
                ]
            );

            return response()->json([
                'data' => [
                    'status' => 'completed',
                    'count_affected' => $count,
                ],
                'message' => "Se ajustaron {$count} obligaciones.",
            ], 202);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'bulk_adjustment_failed',
            ], 409);
        }
    }
}
