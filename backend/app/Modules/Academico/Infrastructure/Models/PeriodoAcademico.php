<?php

namespace App\Modules\Academico\Infrastructure\Models;

use App\Modules\Finanzas\Infrastructure\Models\ConceptoPago;
use App\Modules\Finanzas\Infrastructure\Models\ConfiguracionFinanciera;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoAcademico extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'periodos_academicos';

    protected $fillable = ['nombre', 'tipo', 'fecha_inicio', 'fecha_fin', 'estado', 'creado_por'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function grados(): HasMany
    {
        return $this->hasMany(Grado::class);
    }

    public function configuracionesFinancieras(): HasMany
    {
        return $this->hasMany(ConfiguracionFinanciera::class);
    }

    public function conceptosPago(): HasMany
    {
        return $this->hasMany(ConceptoPago::class);
    }
}
