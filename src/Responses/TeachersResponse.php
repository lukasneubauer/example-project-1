<?php

declare(strict_types=1);

namespace App\Responses;

use App\Http\ApiHeaders;
use App\Http\ApiToken;
use App\Json\JsonEncoder;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use App\Sessions\ApiTokenRefresher;
use Symfony\Component\HttpFoundation\Response;

class TeachersResponse
{
    private ApiToken $apiToken;

    private ApiTokenRefresher $apiTokenRefresher;

    private JsonEncoder $jsonEncoder;

    private ResponseFactory $responseFactory;

    private SessionRepository $sessionRepository;

    private UserRepository $userRepository;

    public function __construct(
        ApiToken $apiToken,
        ApiTokenRefresher $apiTokenRefresher,
        JsonEncoder $jsonEncoder,
        ResponseFactory $responseFactory,
        SessionRepository $sessionRepository,
        UserRepository $userRepository
    ) {
        $this->apiToken = $apiToken;
        $this->apiTokenRefresher = $apiTokenRefresher;
        $this->jsonEncoder = $jsonEncoder;
        $this->responseFactory = $responseFactory;
        $this->sessionRepository = $sessionRepository;
        $this->userRepository = $userRepository;
    }

    public function createResponse(): Response
    {
        $teachers = $this->userRepository->getAllTeachers();
        $teachersArray = [];

        foreach ($teachers as $teacher) {
            $teachersArray[] = [
                'id' => $teacher->getId(),
                'firstName' => $teacher->getFirstName(),
                'lastName' => $teacher->getLastName(),
                'email' => $teacher->getEmail(),
            ];
        }

        $apiToken = $this->apiToken->getApiToken();
        $session = $this->sessionRepository->getByApiToken($apiToken);
        $refreshedSession = $this->apiTokenRefresher->refreshApiTokenIfExpired($session);

        $response = $this->responseFactory->createResponseInstance();
        $response->headers->set(ApiHeaders::API_TOKEN, $refreshedSession->getCurrentApiToken());
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($this->jsonEncoder->encode($teachersArray));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
