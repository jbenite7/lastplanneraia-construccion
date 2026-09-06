<?php

declare(strict_types=1);

namespace App\Services\Lps;

use RuntimeException;

/**
 * Excepción tipada que transporta un {@see LpsApiError}. Es el único vehículo con el que el
 * resolver/política puede fallar: nunca una excepción cruda hacia el controlador.
 */
final class LpsTargetException extends RuntimeException
{
    private LpsApiError $apiError;

    public function __construct(LpsApiError $apiError)
    {
        parent::__construct($apiError->message);
        $this->apiError = $apiError;
    }

    public function apiError(): LpsApiError
    {
        return $this->apiError;
    }
}
