<?php

namespace App\Modules\Finanzas\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoPago extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'movimientos_pago';

    protected $fillable = [
        'obligacion_pago_id',
        'tipo',
        'monto',
        'medio_pago',
        'referencia',
        'numero_recibo',
        'comprobante_ruta',
        'motivo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function obligacionPago(): BelongsTo
    {
        return $this->belongsTo(ObligacionPago::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
