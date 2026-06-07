<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomaliaAsistencia extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'anomalias_asistencia';

    protected $fillable = ['asistencia_alumno_id', 'asistencia_docente_id', 'tipo', 'estado', 'detalle', 'asignado_a', 'resuelto_por', 'resolucion', 'resuelto_en'];

    protected function casts(): array
    {
        return ['resuelto_en' => 'datetime'];
    }

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }
}
