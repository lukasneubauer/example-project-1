<?php

declare(strict_types=1);

namespace App\RequestValidators;

use App\Exceptions\RequestValidationException;

class PingRequestValidator
{
    private RequestValidator $requestValidator;

    public function __construct(RequestValidator $requestValidator)
    {
        $this->requestValidator = $requestValidator;
    }

    /**
     * @throws RequestValidationException
     */
    public function validateRequest(string $method): void
    {
        if ($this->requestValidator->performRequestValidation() === false) {
            return;
        }

        try {
            $this->requestValidator->checkIfHttpMethodIsGet($method);
        } catch (RequestValidationException $e) {
            throw $e;
        }
    }
}
