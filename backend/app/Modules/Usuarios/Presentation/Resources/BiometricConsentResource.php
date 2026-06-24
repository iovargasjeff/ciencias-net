<?php

namespace App\Modules\Usuarios\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BiometricConsentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = match (true) {
            $this->estado === 'revocado' => 'revoked',
            $this->expira_en !== null && $this->expira_en->isPast() => 'expired',
            default => 'active',
        };

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'student_id' => $this->whenLoaded('user', fn () => $this->user?->alumno?->id),
            'student_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'status' => $status,
            'legal_basis' => $this->fundamento_legal,
            'granted_by' => $this->otorgado_por,
            'granted_at' => $this->otorgado_en?->toISOString(),
            'revoked_at' => $this->revocado_en?->toISOString(),
            'revocation_reason' => $this->motivo_revocacion,
            'expires_at' => $this->expira_en?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
