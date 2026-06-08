<?php

namespace App\Modules\Incidencias\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateIncidentReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $filters,
        public string $userId
    ) {}

    public function handle(): void
    {
        // En una implementación real, aquí generaríamos el PDF o Excel usando los filtros
        // y lo guardaríamos en R2, enviando una notificación al usuario cuando termine.
        Log::info("Generando reporte de incidencias para usuario {$this->userId}", $this->filters);

        // Simulación de retraso
        sleep(2);

        Log::info('Reporte de incidencias generado con éxito.');
    }
}
