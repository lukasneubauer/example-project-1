<?php

declare(strict_types=1);

namespace App\Responses;

use Symfony\Component\HttpFoundation\Response;

class PingResponse
{
    private ResponseFactory $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function createResponse(): Response
    {
        $response = $this->responseFactory->createResponseInstance();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(\json_encode(
            [
                'message' => 'pong',
            ]
        ));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
