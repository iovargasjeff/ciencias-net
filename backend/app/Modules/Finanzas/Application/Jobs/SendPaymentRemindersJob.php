<?php

namespace App\Modules\Finanzas\Application\Jobs;

use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Finanzas\Infrastructure\Notifications\PaymentReminderNotification;
use App\Modules\Notificaciones\Infrastructure\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendPaymentRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $obligationIds,
        public readonly string $channel
    ) {}

    public function handle(): void
    {
        $obligations = ObligacionPago::whereIn('id', $this->obligationIds)
            ->where('estado', 'pendiente')
            ->get();

        foreach ($obligations as $obligation) {
            $student = $obligation->alumno;
            if (! $student) {
                continue;
            }

            $parents = $student->padres()
                ->with('user')
                ->wherePivot('recibe_notificaciones', true)
                ->get();

            $applicableAmount = $obligation->getApplicableAmount();

            foreach ($parents as $parent) {
                $parentUser = $parent->user;
                if (! $parentUser) {
                    continue;
                }

                $body = "Estimado padre, le recordamos que tiene una obligación de pago pendiente para {$student->nombres} {$student->apellidos} por un monto de S/ ".number_format($applicableAmount, 2)." con vencimiento el {$obligation->fecha_vencimiento->toDateString()}.";

                // In-App Notification (panel)
                if ($this->channel === 'in_app' || $this->channel === 'both') {
                    Notificacion::create([
                        'user_id' => $parentUser->id,
                        'tipo' => 'pago',
                        'titulo' => 'Recordatorio de Pago',
                        'contenido' => $body,
                        'datos' => [
                            'obligation_id' => $obligation->id,
                            'student_id' => $student->id,
                        ],
                        'canal' => 'panel',
                        'estado' => 'enviada',
                        'enviada_en' => now(),
                    ]);
                }

                // Email Notification (correo)
                if ($this->channel === 'email' || $this->channel === 'both') {
                    // Send via mail channel using Laravel Notification
                    Notification::send($parentUser, new PaymentReminderNotification($student, $obligation, $applicableAmount));

                    // Log the email in notificaciones table with canal = 'correo'
                    Notificacion::create([
                        'user_id' => $parentUser->id,
                        'tipo' => 'pago',
                        'titulo' => 'Recordatorio de Pago',
                        'contenido' => $body,
                        'datos' => [
                            'obligation_id' => $obligation->id,
                            'student_id' => $student->id,
                        ],
                        'canal' => 'correo',
                        'estado' => 'enviada',
                        'enviada_en' => now(),
                    ]);
                }
            }
        }
    }
}
