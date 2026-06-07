<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoAsistencia extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'movimientos_asistencia';

    protected $fillable = ['asistencia_alumno_id', 'asistencia_docente_id', 'tipo', 'motivo', 'observacion', 'ocurrido_en', 'origen', 'estacion_id', 'camara_estacion_id', 'evento_reconocimiento_id', 'confianza_reconocimiento', 'notificacion_enviada', 'registrado_por', 'cuenta_tecnica_id'];

    protected function casts(): array
    {
        return ['ocurrido_en' => 'datetime', 'confianza_reconocimiento' => 'decimal:4', 'notificacion_enviada' => 'boolean'];
    }

    public function asistenciaAlumno(): BelongsTo
    {
        return $this->belongsTo(AsistenciaAlumno::class, 'asistencia_alumno_id');
    }

    public function asistenciaDocente(): BelongsTo
    {
        return $this->belongsTo(AsistenciaDocente::class, 'asistencia_docente_id');
    }
}
