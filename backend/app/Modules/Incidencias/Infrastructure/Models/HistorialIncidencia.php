<?php

namespace App\Modules\Incidencias\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialIncidencia extends Model
{
    use HasUuids;

    protected $table = 'historial_incidencias';

    // No need for updated_at in historial
    public const UPDATED_AT = null;

    protected $fillable = [
        'incidencia_id',
        'accion',
        'detalle',
        'archivo_ruta',
        'registrado_por',
    ];

    public function incidencia(): BelongsTo
    {
        return $this->belongsTo(Incidencia::class, 'incidencia_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
