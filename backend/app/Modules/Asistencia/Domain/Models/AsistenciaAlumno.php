<?php

namespace App\Modules\Asistencia\Domain\Models;

use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsistenciaAlumno extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asistencias_alumnos';

    protected $fillable = ['alumno_id', 'fecha', 'primer_ingreso', 'ultima_salida', 'estado', 'presencia_abierta', 'registrado_por'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'presencia_abierta' => 'boolean'];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoAsistencia::class, 'asistencia_alumno_id');
    }
}
