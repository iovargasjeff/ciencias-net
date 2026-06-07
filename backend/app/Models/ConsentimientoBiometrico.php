<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoBiometrico extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'consentimientos_biometricos';

    protected $fillable = ['user_id', 'estado', 'otorgado_por', 'documento_version', 'otorgado_en', 'revocado_en', 'motivo_revocacion'];

    protected function casts(): array
    {
        return ['otorgado_en' => 'datetime', 'revocado_en' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function otorgante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'otorgado_por');
    }
}
