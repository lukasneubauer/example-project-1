<?php

declare(strict_types=1);

namespace Tests\App\Controllers;

use App\Entities\Password;
use App\Entities\Token;
use App\EntityFactories\UserFactory;
use App\Generators\TokenGenerator;
use App\Passwords\PasswordAlgorithms;
use App\Repositories\UserRepository;
use DateTime;
use Doctrine\Persistence\Mapping\MappingException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Tests\ErrorResponseTester;
use Tests\HttpMethodsDataProvider;
use Tests\ResponseTester;
use Tests\TokenGeneratorWithPredefinedToken;
use Tests\UserFactoryWithPredefinedToken;

final class RegisterControllerFunctionalTest extends WebTestCase
{
    /** @var string */
    public const METHOD = 'POST';

    /** @var string */
    public const ENDPOINT = '/-/register';

    public function testApiKeyIsMissing(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            [],
            '',
            400,
            1,
            "Missing mandatory 'Api-Key' http header."
        );
    }

    public function testApiKeyIsEmpty(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => ''],
            '',
            400,
            2,
            "Missing value for 'Api-Key' http header."
        );
    }

    public function testApiKeyIsInvalid(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => 'xyz'],
            '',
            400,
            3,
            "Invalid value for 'Api-Key' http header."
        );
    }

    /**
     * @dataProvider getInvalidHttpMethods
     */
    public function testHttpMethodIsNotPost(string $invalidHttpMethod): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            $invalidHttpMethod,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '',
            400,
            4,
            "Usage of incorrect http method '$invalidHttpMethod'. 'POST' was expected."
        );
    }

    public function getInvalidHttpMethods(): array
    {
        return HttpMethodsDataProvider::getHttpMethodsExcludingPost();
    }

    public function testMissingJsonInRequestBody(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '',
            400,
            8,
            'Missing JSON in request body.'
        );
    }

    public function testMalformedJsonInRequestBody(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{',
            400,
            9,
            'Malformed JSON in request body.'
        );
    }

    public function testMissingMandatoryPropertyInRequestBodyWhichIsFirstName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{}',
            400,
            10,
            "Missing mandatory property 'firstName' in request body."
        );
    }

    public function testDifferentDataTypeInRequestBodyForPropertyFirstName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":1}',
            400,
            11,
            "Expected string in 'firstName', but got integer in request body."
        );
    }

    public function testDifferentValueInRequestBodyForPropertyFirstName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":""}',
            400,
            12,
            "Expected value in 'firstName', but got \"\" (empty string) in request body."
        );
    }

    public function testStringLengthMustNotBeLongerInRequestBodyForPropertyFirstName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            \sprintf('{"firstName":"%s"}', \str_repeat('a', 256)),
            400,
            55,
            "String length of property 'firstName' must not be longer than 255 characters."
        );
    }

    public function testMissingMandatoryPropertyInRequestBodyWhichIsLastName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John"}',
            400,
            10,
            "Missing mandatory property 'lastName' in request body."
        );
    }

    public function testDifferentDataTypeInRequestBodyForPropertyLastName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":1}',
            400,
            11,
            "Expected string in 'lastName', but got integer in request body."
        );
    }

    public function testDifferentValueInRequestBodyForPropertyLastName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":""}',
            400,
            12,
            "Expected value in 'lastName', but got \"\" (empty string) in request body."
        );
    }

    public function testStringLengthMustNotBeLongerInRequestBodyForPropertyLastName(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            \sprintf('{"firstName":"John","lastName":"%s"}', \str_repeat('a', 256)),
            400,
            55,
            "String length of property 'lastName' must not be longer than 255 characters."
        );
    }

    public function testMissingMandatoryPropertyInRequestBodyWhichIsEmail(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe"}',
            400,
            10,
            "Missing mandatory property 'email' in request body."
        );
    }

    public function testDifferentDataTypeInRequestBodyForPropertyEmail(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":1}',
            400,
            11,
            "Expected string in 'email', but got integer in request body."
        );
    }

    public function testDifferentValueInRequestBodyForPropertyEmail(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":""}',
            400,
            12,
            "Expected value in 'email', but got \"\" (empty string) in request body."
        );
    }

    public function testMalformedEmail(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"malformed.email.com"}',
            400,
            16,
            'Malformed email.'
        );
    }

    public function testStringLengthMustNotBeLongerInRequestBodyForPropertyEmail(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            \sprintf('{"firstName":"John","lastName":"Doe","email":"john.doe@example.com%s"}', \str_repeat('a', 236)),
            400,
            55,
            "String length of property 'email' must not be longer than 255 characters."
        );
    }

    public function testMissingMandatoryPropertyInRequestBodyWhichIsPassword(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com"}',
            400,
            10,
            "Missing mandatory property 'password' in request body."
        );
    }

    public function testDifferentDataTypeInRequestBodyForPropertyPassword(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":1}',
            400,
            11,
            "Expected string in 'password', but got integer in request body."
        );
    }

    public function testDifferentValueInRequestBodyForPropertyPassword(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":""}',
            400,
            12,
            "Expected value in 'password', but got \"\" (empty string) in request body."
        );
    }

    public function testMissingMandatoryPropertyInRequestBodyWhichIsTimezone(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":"secret"}',
            400,
            10,
            "Missing mandatory property 'timezone' in request body."
        );
    }

    public function testDifferentDataTypeInRequestBodyForPropertyTimezone(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":"secret","timezone":1}',
            400,
            11,
            "Expected string in 'timezone', but got integer in request body."
        );
    }

    public function testDifferentValueInRequestBodyForPropertyTimezone(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":"secret","timezone":""}',
            400,
            12,
            "Expected value in 'timezone', but got \"\" (empty string) in request body."
        );
    }

    public function testSelectedTimezoneIsInvalid(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":"secret","isTeacher":true,"isStudent":true,"timezone":"XYZ"}',
            400,
            57,
            "Selected timezone 'XYZ' is invalid."
        );
    }

    public function testValueIsAlreadyTakenForEmail(): void
    {
        ErrorResponseTester::assertError(
            static::createClient(),
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => static::getContainer()->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"john.doe@example.com","password":"secret","timezone":"Europe/Prague"}',
            400,
            14,
            "Value for 'email' in request body is already taken."
        );
    }

    public function testCouldNotGenerateUniqueValueForToken(): void
    {
        $client = self::createClient();

        $dic = static::getContainer();

        /** @var TokenGenerator $tokenGenerator */
        $tokenGenerator = $dic->get(TokenGeneratorWithPredefinedToken::class);
        $dic->set(TokenGenerator::class, $tokenGenerator);

        /** @var UserFactory $userFactory */
        $userFactory = $dic->get(UserFactoryWithPredefinedToken::class);
        $dic->set(UserFactory::class, $userFactory);

        ErrorResponseTester::assertError(
            $client,
            $this,
            self::METHOD,
            self::ENDPOINT,
            ['HTTP_Api-Key' => $dic->getParameter('api_key')],
            '{"firstName":"John","lastName":"Doe","email":"extra-new-john-doe@example.com","password":"secret","timezone":"Europe/Prague"}',
            400,
            25,
            "Could not generate unique value for 'token' in 5 tries."
        );
    }

    /**
     * @throws MappingException
     * @throws RuntimeException
     */
    public function testOk(): void
    {
        $client = self::createClient();

        $dic = static::getContainer();

        $emailAddress = 'extra-new-john-doe@example.com';

        try {
            $client->request(
                self::METHOD,
                self::ENDPOINT,
                [],
                [],
                ['HTTP_Api-Key' => $dic->getParameter('api_key')],
                \sprintf('{"firstName":"John","lastName":"Doe","email":"%s","password":"secret","timezone":"Europe/Prague"}', $emailAddress)
            );

            /** @var Response $response */
            $response = $client->getResponse();
            ResponseTester::assertResponse(200, $response);
            $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
            $this->assertSame('', $response->getContent());

            EntityManagerCleanup::cleanupEntityManager($dic);

            /** @var Email $emailMessage */
            $emailMessage = self::getMailerMessage(0, 'null://');
            $email = $emailMessage->getHtmlBody();
            $this->assertStringContainsString('<title>Registrace</title>', $email);

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);
            $user = $userRepository->getByEmail($emailAddress);
            $this->assertTrue(\is_uuid_valid($user->getId()));
            $this->assertCount(0, $user->getTeacherCourses());
            $this->assertCount(0, $user->getStudentCourses());
            $this->assertCount(0, $user->getSessions());
            $this->assertSame('John', $user->getFirstName());
            $this->assertSame('Doe', $user->getLastName());
            $this->assertSame($emailAddress, $user->getEmail());
            $this->assertInstanceOf(Password::class, $user->getPassword());
            $this->assertTrue(\is_string($user->getPassword()->getHash()));
            $this->assertSame(60, \strlen($user->getPassword()->getHash()));
            $this->assertSame(PasswordAlgorithms::BCRYPT, $user->getPassword()->getAlgorithm());
            $this->assertFalse($user->isTeacher());
            $this->assertFalse($user->isStudent());
            $this->assertSame('Europe/Prague', $user->getTimezone());
            $this->assertInstanceOf(Token::class, $user->getToken());
            $this->assertSame(Token::LENGTH, \strlen($user->getToken()->getCode()));
            $this->assertNull($user->getSecurityCode());
            $this->assertSame(0, $user->getAuthenticationFailures());
            $this->assertFalse($user->isLocked());
            $this->assertFalse($user->isActive());
            $this->assertInstanceOf(DateTime::class, $user->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $user->getUpdatedAt());
            $this->assertSame($user->getCreatedAt()->getTimestamp(), $user->getUpdatedAt()->getTimestamp());
        } catch (RuntimeException $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
