<?php

namespace App\Modules\Finanzas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Application\DTOs\CreatePaymentMovementDTO;
use App\Modules\Finanzas\Domain\Services\PaymentMovementService;
use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Finanzas\Infrastructure\Repositories\EloquentPaymentMovementRepository;
use App\Modules\Finanzas\Presentation\Requests\CreatePaymentMovementRequest;
use App\Modules\Finanzas\Presentation\Resources\PaymentMovementResource;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentMovementController extends Controller
{
    public function __construct(
        private PaymentMovementService $service,
        private EloquentPaymentMovementRepository $repository,
        private AuditLogger $auditLogger
    ) {}

    public function store(CreatePaymentMovementRequest $request): JsonResponse
    {
        $dto = CreatePaymentMovementDTO::fromRequest($request->validated());

        try {
            $movement = match ($dto->movementType) {
                'payment' => $this->handlePayment($dto, $request),
                'reversal' => $this->handleReversal($dto, $request),
                'refund' => $this->handleRefund($dto, $request),
                default => throw new \InvalidArgumentException('Tipo de movimiento inválido.'),
            };

            $this->auditLogger->record(
                $request,
                "finance.movement_{$dto->movementType}",
                $request->user(),
                newValues: [
                    'movement_id' => $movement->id,
                    'obligation_id' => $dto->obligationId,
                    'movement_type' => $dto->movementType,
                    'amount' => $dto->amount,
                ]
            );

            return response()->json([
                'data' => new PaymentMovementResource($movement),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'movement_failed',
            ], 409);
        }
    }

    public function downloadReceipt(string $paymentMovementId, Request $request): JsonResponse
    {
        $request->user()->can('gestionar_finanzas') || abort(403);

        $movement = $this->repository->findOrFail($paymentMovementId);

        if ($movement->tipo !== 'pago') {
            throw new NotFoundHttpException('No hay recibo para movimientos que no son de pago.');
        }

        if (! $movement->comprobante_ruta) {
            return response()->json([
                'data' => [
                    'receipt_number' => $movement->numero_recibo,
                    'amount' => (float) $movement->monto,
                    'method' => $movement->medio_pago,
                    'reference' => $movement->referencia,
                    'paid_at' => $movement->obligacionPago?->fecha_pago?->toIso8601String(),
                    'message' => 'El comprobante PDF aún no ha sido generado.',
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'receipt_number' => $movement->numero_recibo,
                'download_url' => url("/storage/receipts/{$movement->comprobante_ruta}"),
            ],
        ]);
    }

    private function handlePayment(CreatePaymentMovementDTO $dto, Request $request): MovimientoPago
    {
        $obligation = ObligacionPago::findOrFail($dto->obligationId);

        return $this->service->createPayment(
            obligation: $obligation,
            dto: $dto,
            registeredBy: $request->user()
        );
    }

    private function handleReversal(CreatePaymentMovementDTO $dto, Request $request): MovimientoPago
    {
        $originalPayment = MovimientoPago::query()
            ->where('obligacion_pago_id', $dto->obligationId)
            ->where('tipo', 'pago')
            ->firstOrFail();

        return $this->service->createReversal(
            originalPayment: $originalPayment,
            reason: $dto->reason ?? 'Sin motivo especificado',
            registeredBy: $request->user()
        );
    }

    private function handleRefund(CreatePaymentMovementDTO $dto, Request $request): MovimientoPago
    {
        $originalPayment = MovimientoPago::query()
            ->where('obligacion_pago_id', $dto->obligationId)
            ->where('tipo', 'pago')
            ->firstOrFail();

        return $this->service->createRefund(
            originalPayment: $originalPayment,
            refundAmount: $dto->amount,
            reason: $dto->reason ?? 'Sin motivo especificado',
            registeredBy: $request->user()
        );
    }
}
