<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstacionBiometrica extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'estaciones_biometricas';

    protected $fillable = ['codigo', 'nombre', 'ubicacion', 'tipo_equipo', 'cuenta_tecnica_id', 'activo', 'ultimo_contacto', 'configuracion', 'activado_en', 'revocado_en'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'ultimo_contacto' => 'datetime', 'configuracion' => 'array', 'activado_en' => 'datetime', 'revocado_en' => 'datetime'];
    }

    public function cuentaTecnica(): BelongsTo
    {
        return $this->belongsTo(CuentaTecnica::class, 'cuenta_tecnica_id');
    }

    public function camaras(): HasMany
    {
        return $this->hasMany(CamaraEstacion::class, 'estacion_id');
    }
}
