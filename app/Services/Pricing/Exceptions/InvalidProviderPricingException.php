<?php

namespace App\Services\Pricing\Exceptions;

use Exception;
use Throwable;

class InvalidProviderPricingException extends Exception
{
    public function __construct(
        string $message = 'Invalid or malformed provider pricing structure received.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
