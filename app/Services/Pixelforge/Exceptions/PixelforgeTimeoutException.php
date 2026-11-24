<?php

namespace App\Services\Pixelforge\Exceptions;

use Throwable;

class PixelforgeTimeoutException extends PixelforgeException
{
    public function __construct(
        string $message = 'Pixelforge API request timed out.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
