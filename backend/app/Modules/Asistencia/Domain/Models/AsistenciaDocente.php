<?php

namespace App\Modules\Asistencia\Domain\Models;

use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsistenciaDocente extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asistencias_docentes';

    protected $fillable = ['docente_id', 'fecha', 'primer_ingreso', 'ultima_salida', 'estado', 'minutos_tardanza', 'docente_sustituto_id', 'registrado_por'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'minutos_tardanza' => 'integer'];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function docenteSustituto(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_sustituto_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoAsistencia::class, 'asistencia_docente_id');
    }
}
