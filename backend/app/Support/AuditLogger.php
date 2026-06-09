<?php

namespace App\Support;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public function record(
        ?Request $request,
        string $action,
        ?User $user = null,
        Model|string|null $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $subjectType = null,
    ): void {
        $model = $subject instanceof Model ? $subject::class : ($subjectType ?? User::class);
        $modelId = $subject instanceof Model
            ? (string) $subject->getKey()
            : ($subjectType === null
                ? ($user?->id ?? substr(hash('sha256', mb_strtolower((string) $subject)), 0, 36))
                : (string) $subject);

        DB::table('audit_logs')->insert([
            'user_id' => $user?->id,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_values' => $oldValues === null ? null : json_encode($oldValues),
            'new_values' => $newValues === null ? null : json_encode($newValues),
            'ip' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}
