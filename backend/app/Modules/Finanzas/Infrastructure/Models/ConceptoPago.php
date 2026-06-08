<?php

namespace App\Modules\Finanzas\Infrastructure\Models;

use App\Models\PeriodoAcademico;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConceptoPago extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'conceptos_pago';

    protected $fillable = [
        'nombre',
        'tipo',
        'periodo_academico_id',
        'periodo_anio',
        'periodo_mes',
        'monto_base',
        'descuento_pronto_pago',
        'fecha_limite_pronto_pago',
        'estado',
        'bloqueado_en',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto_base' => 'decimal:2',
            'descuento_pronto_pago' => 'decimal:2',
            'fecha_limite_pronto_pago' => 'date',
            'bloqueado_en' => 'datetime',
        ];
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function obligacionesPago(): HasMany
    {
        return $this->hasMany(ObligacionPago::class, 'concepto_id');
    }
}
