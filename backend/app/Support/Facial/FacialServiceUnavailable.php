<?php

namespace App\Support\Facial;

use RuntimeException;
use Throwable;

class FacialServiceUnavailable extends RuntimeException
{
    public function __construct(string $message = 'El servicio facial no está disponible.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
