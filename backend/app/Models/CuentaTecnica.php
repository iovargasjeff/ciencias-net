<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CuentaTecnica extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cuentas_tecnicas';

    protected $fillable = ['nombre', 'tipo', 'token_hash', 'scopes', 'activo', 'creado_por', 'ultimo_contacto', 'token_rotado_en'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'activo' => 'boolean', 'ultimo_contacto' => 'datetime', 'token_rotado_en' => 'datetime'];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function estacionBiometrica(): HasOne
    {
        return $this->hasOne(EstacionBiometrica::class, 'cuenta_tecnica_id');
    }
}
