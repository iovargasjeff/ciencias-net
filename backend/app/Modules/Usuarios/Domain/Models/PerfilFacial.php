<?php

namespace App\Modules\Usuarios\Domain\Models;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerfilFacial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'perfiles_faciales';

    protected $fillable = ['user_id', 'embedding_cifrado', 'modelo_version', 'calidad', 'activo', 'enrolado_por', 'enrolado_en', 'ultima_actualizacion_en'];

    protected function casts(): array
    {
        return ['calidad' => 'decimal:4', 'activo' => 'boolean', 'enrolado_en' => 'datetime', 'ultima_actualizacion_en' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrolador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolado_por');
    }

    public function archivosBiometricos(): HasMany
    {
        return $this->hasMany(ArchivoBiometrico::class, 'perfil_facial_id');
    }
}
