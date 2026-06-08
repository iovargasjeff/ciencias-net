<?php

namespace App\Modules\Finanzas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for bulk operation result.
 */
class BulkOperationResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this['status'] ?? 'pending',
            'count_affected' => $this['count_affected'] ?? 0,
            'count_total' => $this['count_total'] ?? 0,
            'errors' => $this['errors'] ?? [],
            'tracking_id' => $this['tracking_id'] ?? null,
        ];
    }
}
