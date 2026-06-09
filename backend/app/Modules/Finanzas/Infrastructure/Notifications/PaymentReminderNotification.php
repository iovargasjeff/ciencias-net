<?php

namespace App\Modules\Finanzas\Infrastructure\Notifications;

use App\Modules\Finanzas\Infrastructure\Models\ObligacionPago;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Alumno $student,
        private readonly ObligacionPago $obligation,
        private readonly float $amount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[CienciasNET] Recordatorio de Pago Pendiente')
            ->line('Le recordamos que tiene una obligación de pago pendiente para el alumno: '.$this->student->nombres.' '.$this->student->apellidos.'.')
            ->line('Concepto: '.$this->obligation->concepto->nombre)
            ->line('Monto a pagar: S/ '.number_format($this->amount, 2))
            ->line('Fecha de vencimiento: '.$this->obligation->fecha_vencimiento->toDateString())
            ->line('Por favor, realice el pago correspondiente y notifique a la administración.')
            ->line('Gracias por su atención.');
    }
}
