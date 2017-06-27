<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RequestValidationException;
use App\RequestValidators\PingRequestValidator;
use App\Responses\ErrorResponse;
use App\Responses\PingResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PingController
{
    private ErrorResponse $errorResponse;

    private PingRequestValidator $pingRequestValidator;

    private PingResponse $pingResponse;

    public function __construct(
        ErrorResponse $errorResponse,
        PingRequestValidator $pingRequestValidator,
        PingResponse $pingResponse
    ) {
        $this->errorResponse = $errorResponse;
        $this->pingRequestValidator = $pingRequestValidator;
        $this->pingResponse = $pingResponse;
    }

    /**
     * @Route("/-/ping", name="ping")
     */
    public function index(Request $request): Response
    {
        try {
            $this->pingRequestValidator->validateRequest($request->getMethod());
            return $this->pingResponse->createResponse();
        } catch (RequestValidationException $e) {
            return $this->errorResponse->createResponse($e->getData());
        }
    }
}
