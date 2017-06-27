<?php

declare(strict_types=1);

namespace Tests\App\Controllers;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Tests\Database;
use Tests\ErrorResponseTester;
use Tests\HttpMethodsDataProvider;
use Tests\ResponseTester;

final class PingControllerFunctionalTest extends WebTestCase
{
    /** @var string */
    public const METHOD = 'GET';

    /** @var string */
    public const ENDPOINT = '/-/ping';

    /**
     * @dataProvider getInvalidHttpMethods
     */
    public function testHttpMethodIsNotGet(string $invalidHttpMethod): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            $invalidHttpMethod,
            self::ENDPOINT,
            [],
            '',
            400,
            4,
            "Usage of incorrect http method '$invalidHttpMethod'. 'GET' was expected."
        );
    }

    public function getInvalidHttpMethods(): array
    {
        return HttpMethodsDataProvider::getHttpMethodsExcludingGet();
    }

    /**
     * @throws RuntimeException
     */
    public function testOk(): void
    {
        $client = self::createClient();

        $dic = static::getContainer();

        try {
            $client->request(
                self::METHOD,
                self::ENDPOINT
            );

            /** @var Response $response */
            $response = $client->getResponse();
            ResponseTester::assertResponse(200, $response);
            $this->assertSame('application/json', $response->headers->get('Content-Type'));
            $data = \json_decode_get_array((string) $response->getContent());
            $this->assertIsArray($data);
            $this->assertArrayNotHasKey('error', $data);

            $this->assertSame('pong', $data['message']);
        } catch (RuntimeException $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
