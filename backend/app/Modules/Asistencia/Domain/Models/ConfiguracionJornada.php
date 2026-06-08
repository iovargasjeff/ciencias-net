<?php

namespace App\Modules\Asistencia\Domain\Models;

use App\Modules\Academico\Infrastructure\Models\Grado;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionJornada extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'configuraciones_jornada';

    protected $fillable = ['nombre', 'grado_id', 'dia_semana', 'hora_limite_puntual', 'hora_cierre_asistencia', 'activo', 'configurado_por'];

    protected function casts(): array
    {
        return ['dia_semana' => 'integer', 'activo' => 'boolean'];
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }
}
