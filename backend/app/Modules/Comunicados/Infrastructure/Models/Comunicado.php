<?php

namespace App\Modules\Comunicados\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\User;

use App\Modules\Comunicados\Infrastructure\Models\ComunicadoLectura;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comunicado extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'comunicados';

    protected $fillable = [
        'titulo', 'contenido', 'publicado_por',
        'destinatarios', 'importante', 'fecha_publicacion',
    ];

    protected function casts(): array
    {
        return [
            'destinatarios' => 'array',
            'importante' => 'boolean',
            'fecha_publicacion' => 'datetime',
        ];
    }

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(ComunicadoLectura::class);
    }
}
