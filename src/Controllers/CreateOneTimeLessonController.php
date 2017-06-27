<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RequestValidationException;
use App\Exceptions\SessionHasNotMatchingClientIdException;
use App\Json\JsonDecoder;
use App\Persisters\CreateOneTimeLessonPersister;
use App\Persisters\LockSessionPersister;
use App\RequestValidators\CreateOneTimeLessonRequestValidator;
use App\Responses\CreateOneTimeLessonResponse;
use App\Responses\ErrorResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CreateOneTimeLessonController
{
    private CreateOneTimeLessonPersister $createOneTimeLessonPersister;

    private CreateOneTimeLessonRequestValidator $createOneTimeLessonRequestValidator;

    private CreateOneTimeLessonResponse $createOneTimeLessonResponse;

    private ErrorResponse $errorResponse;

    private JsonDecoder $jsonDecoder;

    private LockSessionPersister $lockSessionPersister;

    public function __construct(
        CreateOneTimeLessonPersister $createOneTimeLessonPersister,
        CreateOneTimeLessonRequestValidator $createOneTimeLessonRequestValidator,
        CreateOneTimeLessonResponse $createOneTimeLessonResponse,
        ErrorResponse $errorResponse,
        JsonDecoder $jsonDecoder,
        LockSessionPersister $lockSessionPersister
    ) {
        $this->createOneTimeLessonPersister = $createOneTimeLessonPersister;
        $this->createOneTimeLessonRequestValidator = $createOneTimeLessonRequestValidator;
        $this->createOneTimeLessonResponse = $createOneTimeLessonResponse;
        $this->errorResponse = $errorResponse;
        $this->jsonDecoder = $jsonDecoder;
        $this->lockSessionPersister = $lockSessionPersister;
    }

    /**
     * @Route("/-/create-one-time-lesson", name="create-one-time-lesson")
     */
    public function index(Request $request): Response
    {
        try {
            $requestBody = (string) $request->getContent();
            $data = $this->jsonDecoder->decode($requestBody);
            $this->createOneTimeLessonRequestValidator->validateRequest(
                $request->headers,
                $request->getMethod(),
                $requestBody,
                $data
            );
            $newLesson = $this->createOneTimeLessonPersister->createOneTimeLesson($data);
            return $this->createOneTimeLessonResponse->createResponse($newLesson);
        } catch (RequestValidationException $e) {
            return $this->errorResponse->createResponse($e->getData());
        } catch (SessionHasNotMatchingClientIdException $e) {
            $this->lockSessionPersister->lockSession();
            return $this->errorResponse->createResponse($e->getData());
        }
    }
}
