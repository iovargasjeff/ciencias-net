<?php

namespace App\Modules\Finanzas\Infrastructure\Models;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObligacionPago extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'obligaciones_pago';

    protected $fillable = [
        'alumno_id',
        'concepto_id',
        'monto_base_snapshot',
        'beneficio_id',
        'monto_beneficio_snapshot',
        'descuento_pronto_pago_aplicado',
        'monto_ordinario_snapshot',
        'monto_pronto_pago_snapshot',
        'fecha_limite_pronto_pago_snapshot',
        'monto_cobrado',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'registrado_por',
        'actualizado_finanzas_por',
        'motivo_ultima_modificacion',
    ];

    protected function casts(): array
    {
        return [
            'monto_base_snapshot' => 'decimal:2',
            'monto_beneficio_snapshot' => 'decimal:2',
            'descuento_pronto_pago_aplicado' => 'decimal:2',
            'monto_ordinario_snapshot' => 'decimal:2',
            'monto_pronto_pago_snapshot' => 'decimal:2',
            'monto_cobrado' => 'decimal:2',
            'fecha_limite_pronto_pago_snapshot' => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_pago' => 'datetime',
        ];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(ConceptoPago::class, 'concepto_id');
    }

    public function beneficio(): BelongsTo
    {
        return $this->belongsTo(BeneficioAlumno::class, 'beneficio_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function actualizadoFinanzasPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_finanzas_por');
    }

    public function movimientosPago(): HasMany
    {
        return $this->hasMany(MovimientoPago::class, 'obligacion_pago_id');
    }
}
