<?php

namespace App\Services\Pricing\Exceptions;

use Exception;
use Throwable;

class ProviderPricingUnavailableException extends Exception
{
    public function __construct(
        string $message = 'Pixelforge provider pricing is currently unavailable.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
