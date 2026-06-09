<?php

namespace App\Modules\Notificaciones\Application\Jobs;

use App\Modules\Comunicados\Infrastructure\Models\Comunicado;
use App\Modules\Notificaciones\Infrastructure\Models\Notificacion;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class DistributeAnnouncementNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Comunicado $comunicado
    ) {}

    public function handle(): void
    {
        $userIds = $this->resolveUserIds();

        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'tipo' => 'comunicado',
                'titulo' => $this->comunicado->titulo,
                'contenido' => Str::limit($this->comunicado->contenido, 100),
                'datos' => json_encode(['comunicado_id' => $this->comunicado->id]),
                'canal' => 'panel',
                'estado' => 'pendiente',
            ];
        }

        foreach (array_chunk($notifications, 500) as $chunk) {
            Notificacion::insert($chunk);
        }
    }

    private function resolveUserIds(): array
    {
        $destinatarios = $this->comunicado->destinatarios;

        if (isset($destinatarios['all'])) {
            return User::where('activo', true)->pluck('id')->toArray();
        }

        if (isset($destinatarios['accounts'])) {
            return $destinatarios['accounts'];
        }

        if (isset($destinatarios['roles'])) {
            return User::role($destinatarios['roles'])->where('activo', true)->pluck('id')->toArray();
        }

        if (isset($destinatarios['sections'])) {
            $sectionIds = $destinatarios['sections'];

            // Estudiantes de la seccion
            $studentUserIds = User::whereHas('alumno.matriculas', function ($q) use ($sectionIds) {
                $q->whereIn('seccion_id', $sectionIds)->where('estado', 'activo');
            })->pluck('id')->toArray();

            // Padres de los estudiantes
            $parentUserIds = User::whereHas('padre.alumnos.matriculas', function ($q) use ($sectionIds) {
                $q->whereIn('seccion_id', $sectionIds)->where('estado', 'activo');
            })->pluck('id')->toArray();

            // Docentes de la seccion
            $teacherUserIds = User::whereHas('docente.cargasAcademicas', function ($q) use ($sectionIds) {
                $q->whereIn('seccion_id', $sectionIds)->where('activo', true);
            })->pluck('id')->toArray();

            return array_unique(array_merge($studentUserIds, $parentUserIds, $teacherUserIds));
        }

        return [];
    }
}
