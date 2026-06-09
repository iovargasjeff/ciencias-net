<?php

namespace App\Modules\Finanzas\Domain\Models;

use App\Modules\Usuarios\Infrastructure\Models\Docente;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifaDocente extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'tarifas_docentes';

    protected $fillable = ['docente_id', 'tarifa_hora', 'vigente_desde', 'vigente_hasta', 'registrado_por'];

    protected function casts(): array
    {
        return ['tarifa_hora' => 'decimal:2', 'vigente_desde' => 'date', 'vigente_hasta' => 'date'];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
