<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RequestValidationException;
use App\Exceptions\SessionHasNotMatchingClientIdException;
use App\Persisters\LockSessionPersister;
use App\RequestValidators\TeachersRequestValidator;
use App\Responses\ErrorResponse;
use App\Responses\TeachersResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeachersController
{
    private ErrorResponse $errorResponse;

    private LockSessionPersister $lockSessionPersister;

    private TeachersRequestValidator $teachersRequestValidator;

    private TeachersResponse $teachersResponse;

    public function __construct(
        ErrorResponse $errorResponse,
        LockSessionPersister $lockSessionPersister,
        TeachersRequestValidator $teachersRequestValidator,
        TeachersResponse $teachersResponse
    ) {
        $this->errorResponse = $errorResponse;
        $this->lockSessionPersister = $lockSessionPersister;
        $this->teachersRequestValidator = $teachersRequestValidator;
        $this->teachersResponse = $teachersResponse;
    }

    /**
     * @Route("/-/teachers", name="teachers")
     */
    public function index(Request $request): Response
    {
        try {
            $this->teachersRequestValidator->validateRequest($request->headers, $request->getMethod());
            return $this->teachersResponse->createResponse();
        } catch (RequestValidationException $e) {
            return $this->errorResponse->createResponse($e->getData());
        } catch (SessionHasNotMatchingClientIdException $e) {
            $this->lockSessionPersister->lockSession();
            return $this->errorResponse->createResponse($e->getData());
        }
    }
}
