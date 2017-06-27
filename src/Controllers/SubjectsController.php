<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RequestValidationException;
use App\Exceptions\SessionHasNotMatchingClientIdException;
use App\Persisters\LockSessionPersister;
use App\RequestValidators\SubjectsRequestValidator;
use App\Responses\ErrorResponse;
use App\Responses\SubjectsResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubjectsController
{
    private ErrorResponse $errorResponse;

    private LockSessionPersister $lockSessionPersister;

    private SubjectsRequestValidator $subjectsRequestValidator;

    private SubjectsResponse $subjectsResponse;

    public function __construct(
        ErrorResponse $errorResponse,
        LockSessionPersister $lockSessionPersister,
        SubjectsRequestValidator $subjectsRequestValidator,
        SubjectsResponse $subjectsResponse
    ) {
        $this->errorResponse = $errorResponse;
        $this->lockSessionPersister = $lockSessionPersister;
        $this->subjectsRequestValidator = $subjectsRequestValidator;
        $this->subjectsResponse = $subjectsResponse;
    }

    /**
     * @Route("/-/subjects", name="subjects")
     */
    public function index(Request $request): Response
    {
        try {
            $this->subjectsRequestValidator->validateRequest($request->headers, $request->getMethod());
            return $this->subjectsResponse->createResponse();
        } catch (RequestValidationException $e) {
            return $this->errorResponse->createResponse($e->getData());
        } catch (SessionHasNotMatchingClientIdException $e) {
            $this->lockSessionPersister->lockSession();
            return $this->errorResponse->createResponse($e->getData());
        }
    }
}
