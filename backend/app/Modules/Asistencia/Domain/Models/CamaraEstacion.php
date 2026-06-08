<?php

namespace App\Modules\Asistencia\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CamaraEstacion extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'camaras_estacion';

    protected $fillable = ['estacion_id', 'device_id_navegador', 'nombre', 'ubicacion', 'modo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function estacion(): BelongsTo
    {
        return $this->belongsTo(EstacionBiometrica::class, 'estacion_id');
    }
}
