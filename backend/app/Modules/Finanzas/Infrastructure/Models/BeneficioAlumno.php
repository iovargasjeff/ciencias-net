<?php

namespace App\Modules\Finanzas\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeneficioAlumno extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'beneficios_alumnos';

    protected $fillable = [
        'alumno_id',
        'tipo',
        'modalidad',
        'valor',
        'aplica_mensualidad',
        'aplica_matricula',
        'aplica_cuota_ingreso',
        'acumulable_pronto_pago',
        'vigente_desde',
        'vigente_hasta',
        'motivo',
        'activo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'aplica_mensualidad' => 'boolean',
            'aplica_matricula' => 'boolean',
            'aplica_cuota_ingreso' => 'boolean',
            'acumulable_pronto_pago' => 'boolean',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function obligacionesPago(): HasMany
    {
        return $this->hasMany(ObligacionPago::class, 'beneficio_id');
    }
}
