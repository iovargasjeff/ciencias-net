<?php

namespace App\Modules\Finanzas\Domain\Services;

use App\Modules\Asistencia\Domain\Models\AsistenciaDocente;
use App\Modules\Asistencia\Domain\Models\SesionClase;
use App\Modules\Finanzas\Domain\Models\LiquidacionDescuentoDocente;
use App\Modules\Finanzas\Domain\Models\TarifaDocente;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TeacherPayrollLiquidationService
{
    public function createRate(Docente $teacher, string $hourlyRate, Carbon $from, ?Carbon $until, User $actor): TarifaDocente
    {
        return DB::transaction(function () use ($teacher, $hourlyRate, $from, $until, $actor): TarifaDocente {
            $overlaps = TarifaDocente::query()
                ->where('docente_id', $teacher->id)
                ->whereDate('vigente_desde', '<=', ($until ?? Carbon::parse('9999-12-31'))->toDateString())
                ->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $from->toDateString()))
                ->exists();

            if ($overlaps) {
                throw new ConflictHttpException('La tarifa se superpone con una vigencia existente.');
            }

            return TarifaDocente::create([
                'docente_id' => $teacher->id,
                'tarifa_hora' => $hourlyRate,
                'vigente_desde' => $from->toDateString(),
                'vigente_hasta' => $until?->toDateString(),
                'registrado_por' => $actor->id,
            ]);
        });
    }

    public function createMonthly(Carbon $periodStart, Carbon $periodEnd, array $teacherIds, User $actor): array
    {
        return DB::transaction(function () use ($periodStart, $periodEnd, $teacherIds, $actor): array {
            $periodYear = (int) $periodStart->format('Y');
            $periodMonth = (int) $periodStart->format('m');
            $teachers = Docente::query()->when($teacherIds !== [], fn ($q) => $q->whereIn('id', $teacherIds))->get();
            $liquidations = [];

            foreach ($teachers as $teacher) {
                $existing = LiquidacionDescuentoDocente::where('docente_id', $teacher->id)
                    ->where('periodo_anio', $periodYear)
                    ->where('periodo_mes', $periodMonth)
                    ->first();
                if ($existing?->estado === 'cerrada') {
                    throw new ConflictHttpException('La liquidación cerrada no admite recálculo.');
                }

                $rate = $this->rateFor($teacher, $periodEnd);
                $lateMinutes = AsistenciaDocente::query()
                    ->where('docente_id', $teacher->id)
                    ->whereBetween('fecha', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->sum('minutos_tardanza');
                $justifiedHours = $this->absenceHours($teacher, $periodStart, $periodEnd, 'falta_justificada');
                $unjustifiedHours = $this->unjustifiedAbsenceHours($teacher, $periodStart, $periodEnd);
                $lateAmount = round(($lateMinutes / 60) * $rate, 2);
                $justifiedAmount = round($justifiedHours * $rate, 2);
                $unjustifiedAmount = round($unjustifiedHours * $rate * 2, 2);
                $total = round($lateAmount + $justifiedAmount + $unjustifiedAmount, 2);

                $liquidations[] = LiquidacionDescuentoDocente::updateOrCreate([
                    'docente_id' => $teacher->id,
                    'periodo_anio' => $periodYear,
                    'periodo_mes' => $periodMonth,
                ], [
                    'tarifa_hora_snapshot' => $rate,
                    'minutos_tardanza' => $lateMinutes,
                    'horas_falta_justificada' => $justifiedHours,
                    'horas_falta_injustificada' => $unjustifiedHours,
                    'monto_tardanza' => $lateAmount,
                    'monto_falta_justificada' => $justifiedAmount,
                    'monto_falta_injustificada' => $unjustifiedAmount,
                    'monto_ajuste' => $existing?->monto_ajuste ?? 0,
                    'motivo_ajuste' => $existing?->motivo_ajuste,
                    'monto_total_descuento' => $total + (float) ($existing?->monto_ajuste ?? 0),
                    'estado' => 'borrador',
                    'calculado_por' => $actor->id,
                    'cerrada_por' => null,
                    'cerrada_en' => null,
                ])->refresh();
            }

            return $liquidations;
        });
    }

    public function close(LiquidacionDescuentoDocente $liquidation, User $actor): LiquidacionDescuentoDocente
    {
        if ($liquidation->estado === 'cerrada') {
            throw new ConflictHttpException('La liquidación ya está cerrada.');
        }

        $liquidation->update([
            'estado' => 'cerrada',
            'cerrada_por' => $actor->id,
            'cerrada_en' => now(),
        ]);

        return $liquidation->refresh();
    }

    private function rateFor(Docente $teacher, Carbon $date): float
    {
        $rate = TarifaDocente::where('docente_id', $teacher->id)
            ->whereDate('vigente_desde', '<=', $date->toDateString())
            ->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $date->toDateString()))
            ->latest('vigente_desde')
            ->first();

        if ($rate === null) {
            throw new ConflictHttpException('No existe tarifa vigente para el docente.');
        }

        return (float) $rate->tarifa_hora;
    }

    private function absenceHours(Docente $teacher, Carbon $periodStart, Carbon $periodEnd, string $status): float
    {
        return AsistenciaDocente::query()
            ->where('docente_id', $teacher->id)
            ->where('estado', $status)
            ->whereBetween('fecha', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get()
            ->sum(fn (AsistenciaDocente $attendance): float => $this->scheduledHours($teacher, $attendance->fecha));
    }

    private function unjustifiedAbsenceHours(Docente $teacher, Carbon $periodStart, Carbon $periodEnd): float
    {
        return SesionClase::query()
            ->where('estado', 'docente_ausente')
            ->whereBetween('fecha', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->where(function ($query) use ($teacher): void {
                $query->where('docente_sustituto_id', $teacher->id)
                    ->orWhereHas('cargaAcademica', fn ($assignment) => $assignment->where('docente_id', $teacher->id));
            })
            ->get()
            ->sum(fn (SesionClase $session): float => $this->sessionHours($session));
    }

    private function scheduledHours(Docente $teacher, Carbon $date): float
    {
        return SesionClase::query()
            ->whereDate('fecha', $date->toDateString())
            ->where('estado', '!=', 'cancelada')
            ->where(function ($query) use ($teacher): void {
                $query->where('docente_sustituto_id', $teacher->id)
                    ->orWhereHas('cargaAcademica', fn ($assignment) => $assignment->where('docente_id', $teacher->id));
            })
            ->get()
            ->sum(fn (SesionClase $session): float => $this->sessionHours($session));
    }

    private function sessionHours(SesionClase $session): float
    {
        $start = Carbon::parse($session->fecha->toDateString().' '.$session->hora_inicio);
        $end = Carbon::parse($session->fecha->toDateString().' '.$session->hora_fin);

        return round($start->diffInMinutes($end) / 60, 2);
    }
}
