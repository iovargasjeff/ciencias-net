<?php

namespace App\Notifications;

use App\Models\Alumno;
use App\Models\MovimientoAsistencia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAttendanceMovementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Alumno $student, private readonly MovimientoAsistencia $movement) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[CienciasNET] Movimiento de asistencia')
            ->line('Se registró un movimiento de asistencia para '.$this->student->nombres.' '.$this->student->apellidos.'.')
            ->line('Tipo: '.$this->movement->tipo)
            ->line('Hora: '.$this->movement->ocurrido_en->format('Y-m-d H:i:s'));
    }
}
