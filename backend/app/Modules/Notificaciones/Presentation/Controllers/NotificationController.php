<?php

namespace App\Modules\Notificaciones\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notificaciones\Infrastructure\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function listNotifications(Request $request): JsonResponse
    {
        $notifications = Notificacion::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($notifications);
    }
}
