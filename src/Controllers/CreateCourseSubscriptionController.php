<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RequestValidationException;
use App\Exceptions\SessionHasNotMatchingClientIdException;
use App\Json\JsonDecoder;
use App\Persisters\CreateCourseSubscriptionPersister;
use App\Persisters\LockSessionPersister;
use App\RequestValidators\CreateCourseSubscriptionRequestValidator;
use App\Responses\EmptySuccessfulResponseWithApiToken;
use App\Responses\ErrorResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CreateCourseSubscriptionController
{
    private CreateCourseSubscriptionPersister $createCourseSubscriptionPersister;

    private CreateCourseSubscriptionRequestValidator $createCourseSubscriptionRequestValidator;

    private EmptySuccessfulResponseWithApiToken $emptySuccessfulResponseWithApiToken;

    private ErrorResponse $errorResponse;

    private JsonDecoder $jsonDecoder;

    private LockSessionPersister $lockSessionPersister;

    public function __construct(
        CreateCourseSubscriptionPersister $createCourseSubscriptionPersister,
        CreateCourseSubscriptionRequestValidator $createCourseSubscriptionRequestValidator,
        EmptySuccessfulResponseWithApiToken $emptySuccessfulResponseWithApiToken,
        ErrorResponse $errorResponse,
        JsonDecoder $jsonDecoder,
        LockSessionPersister $lockSessionPersister
    ) {
        $this->createCourseSubscriptionPersister = $createCourseSubscriptionPersister;
        $this->createCourseSubscriptionRequestValidator = $createCourseSubscriptionRequestValidator;
        $this->emptySuccessfulResponseWithApiToken = $emptySuccessfulResponseWithApiToken;
        $this->errorResponse = $errorResponse;
        $this->jsonDecoder = $jsonDecoder;
        $this->lockSessionPersister = $lockSessionPersister;
    }

    /**
     * @Route("/-/create-course-subscription", name="create-course-subscription")
     */
    public function index(Request $request): Response
    {
        try {
            $requestBody = (string) $request->getContent();
            $data = $this->jsonDecoder->decode($requestBody);
            $this->createCourseSubscriptionRequestValidator->validateRequest(
                $request->headers,
                $request->getMethod(),
                $requestBody,
                $data
            );
            $this->createCourseSubscriptionPersister->createCourseSubscription($data);
            return $this->emptySuccessfulResponseWithApiToken->createResponse();
        } catch (RequestValidationException $e) {
            return $this->errorResponse->createResponse($e->getData());
        } catch (SessionHasNotMatchingClientIdException $e) {
            $this->lockSessionPersister->lockSession();
            return $this->errorResponse->createResponse($e->getData());
        }
    }
}
