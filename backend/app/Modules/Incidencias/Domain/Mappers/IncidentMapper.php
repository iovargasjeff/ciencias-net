<?php

namespace App\Modules\Incidencias\Domain\Mappers;

class IncidentMapper
{
    public static function severityToDb(string $apiSeverity): string
    {
        return match ($apiSeverity) {
            'low' => 'leve',
            'medium' => 'moderada',
            'high', 'critical' => 'grave',
            default => 'leve',
        };
    }

    public static function severityToApi(string $dbSeverity): string
    {
        return match ($dbSeverity) {
            'leve' => 'low',
            'moderada' => 'medium',
            'grave' => 'high',
            default => 'low',
        };
    }

    public static function statusToDb(string $apiStatus): string
    {
        return match ($apiStatus) {
            'open', 'in_progress' => 'abierto',
            'referred_toe' => 'derivado_toe',
            'referred_psychology' => 'derivado_psicologia',
            'parent_notified' => 'notificado_padre',
            'resolved', 'closed' => 'resuelto',
            default => 'abierto',
        };
    }

    public static function statusToApi(string $dbStatus): string
    {
        return match ($dbStatus) {
            'abierto' => 'open',
            'derivado_toe' => 'referred_toe',
            'derivado_psicologia' => 'referred_psychology',
            'notificado_padre' => 'parent_notified',
            'resuelto' => 'resolved',
            default => 'open',
        };
    }
}
