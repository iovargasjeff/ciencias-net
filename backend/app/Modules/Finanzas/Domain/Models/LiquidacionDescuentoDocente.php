<?php

namespace App\Modules\Finanzas\Domain\Models;

use App\Modules\Usuarios\Infrastructure\Models\Docente;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionDescuentoDocente extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'liquidaciones_descuento_docentes';

    protected $fillable = ['docente_id', 'periodo_anio', 'periodo_mes', 'tarifa_hora_snapshot', 'minutos_tardanza', 'horas_falta_justificada', 'horas_falta_injustificada', 'monto_tardanza', 'monto_falta_justificada', 'monto_falta_injustificada', 'monto_ajuste', 'motivo_ajuste', 'monto_total_descuento', 'estado', 'calculado_por', 'cerrada_por', 'cerrada_en'];

    protected function casts(): array
    {
        return ['periodo_anio' => 'integer', 'periodo_mes' => 'integer', 'tarifa_hora_snapshot' => 'decimal:2', 'minutos_tardanza' => 'integer', 'horas_falta_justificada' => 'decimal:2', 'horas_falta_injustificada' => 'decimal:2', 'monto_tardanza' => 'decimal:2', 'monto_falta_justificada' => 'decimal:2', 'monto_falta_injustificada' => 'decimal:2', 'monto_ajuste' => 'decimal:2', 'monto_total_descuento' => 'decimal:2', 'cerrada_en' => 'datetime'];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
