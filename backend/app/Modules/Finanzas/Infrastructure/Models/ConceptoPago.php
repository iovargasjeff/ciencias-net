<?php

namespace App\Modules\Finanzas\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Usuarios\Infrastructure\Models\User;
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
        'codigo',
        'tipo',
        'periodo_academico_id',
        'periodo_anio',
        'periodo_mes',
        'monto_base',
        'descuento_pronto_pago',
        'fecha_limite_pronto_pago',
        'estado',
        'bloqueado_en',
        'vigente_desde',
        'vigente_hasta',
        'reemplaza_concepto_id',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto_base' => 'decimal:2',
            'descuento_pronto_pago' => 'decimal:2',
            'fecha_limite_pronto_pago' => 'date',
            'bloqueado_en' => 'datetime',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
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

    public function reemplazaConcepto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reemplaza_concepto_id');
    }
}
