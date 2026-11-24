<?php

namespace App\Services\Pricing\Exceptions;

use InvalidArgumentException;
use Throwable;

class InvalidEstimationInputException extends InvalidArgumentException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
