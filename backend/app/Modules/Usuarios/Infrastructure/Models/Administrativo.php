<?php

namespace App\Modules\Usuarios\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Administrativo extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'nombres', 'cargo'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
