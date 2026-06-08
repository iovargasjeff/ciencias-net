<?php

namespace App\Modules\Usuarios\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Padre extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'dni', 'nombres', 'apellidos', 'celular', 'correo_notificaciones'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(Alumno::class, 'alumno_padre')
            ->withPivot(['relacion', 'es_contacto_principal', 'recibe_notificaciones']);
    }
}
