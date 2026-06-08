<?php

namespace App\Modules\Usuarios\Domain\Models;

use App\Modules\Usuarios\Infrastructure\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoBiometrico extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'archivos_biometricos';

    protected $fillable = ['user_id', 'perfil_facial_id', 'tipo', 'r2_object_key', 'sha256', 'mime_type', 'expira_en', 'eliminado_en'];

    protected function casts(): array
    {
        return ['expira_en' => 'datetime', 'eliminado_en' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function perfilFacial(): BelongsTo
    {
        return $this->belongsTo(PerfilFacial::class, 'perfil_facial_id');
    }
}
