<?php

namespace App\Modules\Usuarios\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Finanzas\Infrastructure\Models\BeneficioAlumno;
use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumno extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'dni', 'nombres', 'apellidos'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function padres(): BelongsToMany
    {
        return $this->belongsToMany(Padre::class, 'alumno_padre')
            ->withPivot(['relacion', 'es_contacto_principal', 'recibe_notificaciones']);
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function beneficiosFinancieros(): HasMany
    {
        return $this->hasMany(BeneficioAlumno::class);
    }

    public function obligacionesPago(): HasMany
    {
        return $this->hasMany(ObligacionPago::class);
    }
}
