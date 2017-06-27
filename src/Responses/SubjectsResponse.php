<?php

declare(strict_types=1);

namespace App\Responses;

use App\Http\ApiHeaders;
use App\Http\ApiToken;
use App\Json\JsonEncoder;
use App\Repositories\SessionRepository;
use App\Repositories\SubjectRepository;
use App\Sessions\ApiTokenRefresher;
use Symfony\Component\HttpFoundation\Response;

class SubjectsResponse
{
    private ApiToken $apiToken;

    private ApiTokenRefresher $apiTokenRefresher;

    private JsonEncoder $jsonEncoder;

    private ResponseFactory $responseFactory;

    private SessionRepository $sessionRepository;

    private SubjectRepository $subjectRepository;

    public function __construct(
        ApiToken $apiToken,
        ApiTokenRefresher $apiTokenRefresher,
        JsonEncoder $jsonEncoder,
        ResponseFactory $responseFactory,
        SessionRepository $sessionRepository,
        SubjectRepository $subjectRepository
    ) {
        $this->apiToken = $apiToken;
        $this->apiTokenRefresher = $apiTokenRefresher;
        $this->jsonEncoder = $jsonEncoder;
        $this->responseFactory = $responseFactory;
        $this->sessionRepository = $sessionRepository;
        $this->subjectRepository = $subjectRepository;
    }

    public function createResponse(): Response
    {
        $subjects = $this->subjectRepository->getAll();
        $subjectsArray = [];

        foreach ($subjects as $subject) {
            $subjectsArray[] = [
                'id' => $subject->getId(),
                'name' => $subject->getName(),
            ];
        }

        $apiToken = $this->apiToken->getApiToken();
        $session = $this->sessionRepository->getByApiToken($apiToken);
        $refreshedSession = $this->apiTokenRefresher->refreshApiTokenIfExpired($session);

        $response = $this->responseFactory->createResponseInstance();
        $response->headers->set(ApiHeaders::API_TOKEN, $refreshedSession->getCurrentApiToken());
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($this->jsonEncoder->encode($subjectsArray));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
