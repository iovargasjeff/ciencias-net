<?php

namespace App\Support;

use Monolog\LogRecord;

class RedactSensitiveLogContext
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: SensitiveDataRedactor::redact($record->context),
            extra: SensitiveDataRedactor::redact($record->extra),
        );
    }
}
