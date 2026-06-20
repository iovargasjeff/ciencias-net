<?php

namespace App\Modules\Finanzas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Application\Jobs\SendPaymentRemindersJob;
use App\Modules\Finanzas\Infrastructure\Models\MovimientoPago;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Finanzas\Presentation\Requests\SendPaymentRemindersRequest;
use App\Modules\Finanzas\Presentation\Resources\PaymentObligationResource;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FinanceQueryController extends Controller
{
    public function listAccountStatements(Request $request): JsonResponse
    {
        $studentId = $request->query('student_id');
        $studentIds = [];

        if (! $studentId) {
            $alumno = $request->user()->alumno;
            if ($alumno) {
                $studentId = $alumno->id;
            } elseif ($request->user()->padre) {
                $studentIds = $request->user()->padre->alumnos()->pluck('alumnos.id')->all();
                if ($studentIds === []) {
                    return response()->json([
                        'data' => [],
                        'meta' => [
                            'current_page' => 1,
                            'from' => null,
                            'last_page' => 1,
                            'per_page' => min($request->integer('per_page', 20), 100),
                            'to' => null,
                            'total' => 0,
                        ],
                        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
                    ]);
                }
            } else {
                return response()->json([
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => 'El parámetro student_id es requerido.',
                        'fields' => [
                            'student_id' => ['El student_id es obligatorio para usuarios administrativos o padres.'],
                        ],
                    ],
                ], 422);
            }
        }

        if ($studentId) {
            $alumno = Alumno::findOrFail($studentId);

            if (! $request->user()->can('gestionar_finanzas')) {
                if (! $request->user()->can('viewLinked', $alumno)) {
                    return response()->json([
                        'error' => [
                            'code' => 'forbidden',
                            'message' => 'No tienes permiso para consultar el estado de cuenta de este alumno.',
                            'fields' => (object) [],
                        ],
                    ], 403);
                }
            }

            $studentIds = [$alumno->id];
        }

        $query = ObligacionPago::query()
            ->whereIn('alumno_id', $studentIds)
            ->with(['concepto', 'beneficio'])
            ->latest('fecha_vencimiento');

        $perPage = min($request->integer('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => PaymentObligationResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'from' => $paginated->firstItem(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'to' => $paginated->lastItem(),
                'total' => $paginated->total(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ]);
    }

    public function listDebtors(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('gestionar_finanzas'), 403);

        $today = Carbon::today()->toDateString();

        $query = Alumno::query()
            ->whereHas('obligacionesPago', function ($q) use ($today) {
                $q->whereIn('estado', ['pendiente', 'vencido'])
                    ->where('fecha_vencimiento', '<', $today);
            })
            ->with(['user'])
            ->withCount(['obligacionesPago as overdue_count' => function ($q) use ($today) {
                $q->whereIn('estado', ['pendiente', 'vencido'])
                    ->where('fecha_vencimiento', '<', $today);
            }]);

        $perPage = min($request->integer('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        $debtorsData = collect($paginated->items())->map(function ($alumno) use ($today) {
            // Fetch the overdue obligations with concepts
            $overdueObligations = $alumno->obligacionesPago()
                ->whereIn('estado', ['pendiente', 'vencido'])
                ->where('fecha_vencimiento', '<', $today)
                ->with(['concepto', 'beneficio'])
                ->get();

            $totalOverdue = $overdueObligations->sum(fn ($o) => (float) $o->monto_ordinario_snapshot);

            return [
                'id' => $alumno->id,
                'student' => [
                    'id' => $alumno->id,
                    'name' => $alumno->nombres.' '.$alumno->apellidos,
                    'email' => $alumno->user?->email,
                ],
                'overdue_count' => $alumno->overdue_count,
                'total_overdue_amount' => $totalOverdue,
                'overdue_obligations' => PaymentObligationResource::collection($overdueObligations),
            ];
        });

        return response()->json([
            'data' => $debtorsData,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'from' => $paginated->firstItem(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'to' => $paginated->lastItem(),
                'total' => $paginated->total(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ]);
    }

    public function getCashReport(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('gestionar_finanzas'), 403);

        $dateFrom = $request->query('date_from')
            ? Carbon::parse($request->query('date_from'))->startOfDay()
            : Carbon::today()->startOfDay();
        $dateTo = $request->query('date_to')
            ? Carbon::parse($request->query('date_to'))->endOfDay()
            : Carbon::today()->endOfDay();

        $movements = MovimientoPago::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get();

        $totalCollected = $movements->where('tipo', 'pago')->sum(fn ($m) => (float) $m->monto);
        $totalReversed = $movements->where('tipo', 'anulacion')->sum(fn ($m) => (float) $m->monto);
        $totalRefunded = $movements->where('tipo', 'devolucion')->sum(fn ($m) => (float) $m->monto);
        $netAmount = $totalCollected - $totalReversed - $totalRefunded;

        $byMethod = [
            'efectivo' => 0.0,
            'transferencia' => 0.0,
            'yape' => 0.0,
            'plin' => 0.0,
            'otro' => 0.0,
        ];

        foreach ($movements->where('tipo', 'pago') as $movement) {
            $method = $movement->medio_pago ?? 'otro';
            if (array_key_exists($method, $byMethod)) {
                $byMethod[$method] += (float) $movement->monto;
            } else {
                $byMethod['otro'] += (float) $movement->monto;
            }
        }

        return response()->json([
            'data' => [
                'id' => 'cash-report-'.$dateFrom->toDateString().'-to-'.$dateTo->toDateString(),
                'date_from' => $dateFrom->toIso8601String(),
                'date_to' => $dateTo->toIso8601String(),
                'total_collected' => $totalCollected,
                'total_reversed' => $totalReversed,
                'total_refunded' => $totalRefunded,
                'net_amount' => $netAmount,
                'by_method' => $byMethod,
            ],
        ]);
    }

    public function sendPaymentReminders(SendPaymentRemindersRequest $request): JsonResponse
    {
        $obligationIds = $request->input('obligation_ids');
        $channel = $request->input('channel');

        // Dispatch background job
        SendPaymentRemindersJob::dispatch($obligationIds, $channel);

        return response()->json([
            'data' => [
                'id' => (string) Str::uuid(),
                'status' => 'pending',
                'obligation_ids' => $obligationIds,
                'channel' => $channel,
            ],
        ], 202);
    }
}
