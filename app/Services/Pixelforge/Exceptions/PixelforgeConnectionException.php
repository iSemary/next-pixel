<?php

namespace App\Services\Pixelforge\Exceptions;

use Throwable;

class PixelforgeConnectionException extends PixelforgeException
{
    public function __construct(
        string $message = 'Unable to connect to Pixelforge API.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
