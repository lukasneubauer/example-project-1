<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RequestValidationException;
use App\Exceptions\SessionHasNotMatchingClientIdException;
use App\Json\JsonDecoder;
use App\Persisters\LockSessionPersister;
use App\Persisters\SendPaymentPersister;
use App\RequestValidators\SendPaymentRequestValidator;
use App\Responses\EmptySuccessfulResponseWithApiToken;
use App\Responses\ErrorResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SendPaymentController
{
    private EmptySuccessfulResponseWithApiToken $emptySuccessfulResponseWithApiToken;

    private ErrorResponse $errorResponse;

    private JsonDecoder $jsonDecoder;

    private LockSessionPersister $lockSessionPersister;

    private SendPaymentPersister $sendPaymentPersister;

    private SendPaymentRequestValidator $sendPaymentRequestValidator;

    public function __construct(
        EmptySuccessfulResponseWithApiToken $emptySuccessfulResponseWithApiToken,
        ErrorResponse $errorResponse,
        JsonDecoder $jsonDecoder,
        LockSessionPersister $lockSessionPersister,
        SendPaymentPersister $sendPaymentPersister,
        SendPaymentRequestValidator $sendPaymentRequestValidator
    ) {
        $this->emptySuccessfulResponseWithApiToken = $emptySuccessfulResponseWithApiToken;
        $this->errorResponse = $errorResponse;
        $this->jsonDecoder = $jsonDecoder;
        $this->lockSessionPersister = $lockSessionPersister;
        $this->sendPaymentPersister = $sendPaymentPersister;
        $this->sendPaymentRequestValidator = $sendPaymentRequestValidator;
    }

    /**
     * @Route("/-/send-payment", name="send-payment")
     */
    public function index(Request $request): Response
    {
        try {
            $requestBody = (string) $request->getContent();
            $data = $this->jsonDecoder->decode($requestBody);
            $this->sendPaymentRequestValidator->validateRequest(
                $request->headers,
                $request->getMethod(),
                $requestBody,
                $data
            );
            $this->sendPaymentPersister->sendPayment($data);
            return $this->emptySuccessfulResponseWithApiToken->createResponse();
        } catch (RequestValidationException $e) {
            return $this->errorResponse->createResponse($e->getData());
        } catch (SessionHasNotMatchingClientIdException $e) {
            $this->lockSessionPersister->lockSession();
            return $this->errorResponse->createResponse($e->getData());
        }
    }
}
