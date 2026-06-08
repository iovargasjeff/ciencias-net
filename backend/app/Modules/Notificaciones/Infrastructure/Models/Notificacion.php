<?php

namespace App\Modules\Notificaciones\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notificaciones';

    // Solo tiene created_at, no updated_at
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'user_id', 'tipo', 'titulo', 'contenido', 'datos',
        'canal', 'estado', 'enviada_en', 'leida_en',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'enviada_en' => 'datetime',
            'leida_en' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
