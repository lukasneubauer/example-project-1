<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Password;
use App\Entities\User;
use App\EntityFactories\SessionFactory;
use App\Exceptions\CouldNotPersistException;
use App\Generators\ApiTokenGenerator;
use App\Http\ApiHeaders;
use App\Passwords\PasswordAlgorithms;
use App\Passwords\PasswordSettings;
use App\Persisters\LoginPersister;
use App\Repositories\SessionRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\ApiTokenGeneratorWithPredefinedApiToken;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Tests\PasswordSettingsWithPredefinedValues;
use Tests\SessionFactoryWithPredefinedApiToken;
use Throwable;

final class LoginPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateSession(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiClientId = 'CLIENT-ID';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_CLIENT_ID, $apiClientId);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var LoginPersister $loginPersister */
            $loginPersister = $dic->get(LoginPersister::class);

            $newSession = $loginPersister->createSession([
                'email' => 'john.doe@example.com',
                'password' => 'secret',
            ]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($newSession->getCurrentApiToken());

            $this->assertSame(36, \strlen($session->getId()));
            $this->assertInstanceOf(User::class, $session->getUser());
            $this->assertSame('8a06562a-c59a-4477-9e0a-ab8b9aba947b', $session->getUser()->getId());
            $this->assertSame('John', $session->getUser()->getFirstName());
            $this->assertSame('Doe', $session->getUser()->getLastName());
            $this->assertSame('john.doe@example.com', $session->getUser()->getEmail());
            $this->assertInstanceOf(Password::class, $session->getUser()->getPassword());
            $this->assertSame(60, \strlen($session->getUser()->getPassword()->getHash()));
            $this->assertStringStartsWith('$2y$13$', $session->getUser()->getPassword()->getHash());
            $this->assertSame(PasswordAlgorithms::BCRYPT, $session->getUser()->getPassword()->getAlgorithm());
            $this->assertSame(0, $session->getUser()->getAuthenticationFailures());
            $this->assertSame('CLIENT-ID', $session->getApiClientId());
            $this->assertNull($session->getOldApiToken());
            $this->assertSame(80, \strlen($session->getCurrentApiToken()));
            $this->assertInstanceOf(DateTime::class, $session->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $session->getRefreshedAt());
            $this->assertSame($session->getCreatedAt()->getTimestamp(), $session->getRefreshedAt()->getTimestamp());
            $this->assertFalse($session->isLocked());
            $this->assertInstanceOf(DateTime::class, $session->getUpdatedAt());
            $this->assertSame($session->getCreatedAt()->getTimestamp(), $session->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateSessionWillResetAuthenticationFailures(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiClientId = 'CLIENT-ID';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_CLIENT_ID, $apiClientId);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var LoginPersister $loginPersister */
            $loginPersister = $dic->get(LoginPersister::class);

            $newSession = $loginPersister->createSession([
                'email' => 'zack.doe@example.com',
                'password' => 'secret',
            ]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($newSession->getCurrentApiToken());

            $this->assertSame(36, \strlen($session->getId()));
            $this->assertInstanceOf(User::class, $session->getUser());
            $this->assertSame('c6b72c4f-80e3-40c2-9377-c718c9adaf91', $session->getUser()->getId());
            $this->assertSame('Zack', $session->getUser()->getFirstName());
            $this->assertSame('Doe', $session->getUser()->getLastName());
            $this->assertSame('zack.doe@example.com', $session->getUser()->getEmail());
            $this->assertInstanceOf(Password::class, $session->getUser()->getPassword());
            $this->assertSame(60, \strlen($session->getUser()->getPassword()->getHash()));
            $this->assertStringStartsWith('$2y$13$', $session->getUser()->getPassword()->getHash());
            $this->assertSame(PasswordAlgorithms::BCRYPT, $session->getUser()->getPassword()->getAlgorithm());
            $this->assertSame(0, $session->getUser()->getAuthenticationFailures());
            $this->assertSame('CLIENT-ID', $session->getApiClientId());
            $this->assertNull($session->getOldApiToken());
            $this->assertSame(80, \strlen($session->getCurrentApiToken()));
            $this->assertInstanceOf(DateTime::class, $session->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $session->getRefreshedAt());
            $this->assertSame($session->getCreatedAt()->getTimestamp(), $session->getRefreshedAt()->getTimestamp());
            $this->assertFalse($session->isLocked());
            $this->assertInstanceOf(DateTime::class, $session->getUpdatedAt());
            $this->assertSame($session->getCreatedAt()->getTimestamp(), $session->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateSessionWillRehashPassword(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiClientId = 'CLIENT-ID';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_CLIENT_ID, $apiClientId);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var PasswordSettings $passwordSettings */
            $passwordSettings = $dic->get(PasswordSettingsWithPredefinedValues::class);
            $dic->set(PasswordSettings::class, $passwordSettings);

            /** @var LoginPersister $loginPersister */
            $loginPersister = $dic->get(LoginPersister::class);

            $newSession = $loginPersister->createSession([
                'email' => 'john.doe@example.com',
                'password' => 'secret',
            ]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($newSession->getCurrentApiToken());

            $this->assertSame(36, \strlen($session->getId()));
            $this->assertInstanceOf(User::class, $session->getUser());
            $this->assertSame('8a06562a-c59a-4477-9e0a-ab8b9aba947b', $session->getUser()->getId());
            $this->assertSame('John', $session->getUser()->getFirstName());
            $this->assertSame('Doe', $session->getUser()->getLastName());
            $this->assertSame('john.doe@example.com', $session->getUser()->getEmail());
            $this->assertInstanceOf(Password::class, $session->getUser()->getPassword());
            $this->assertSame(96, \strlen($session->getUser()->getPassword()->getHash()));
            $this->assertStringStartsWith('$argon2i$v=19$m=65536,t=4,p=1$', $session->getUser()->getPassword()->getHash());
            $this->assertSame(PasswordAlgorithms::ARGON2I, $session->getUser()->getPassword()->getAlgorithm());
            $this->assertSame(0, $session->getUser()->getAuthenticationFailures());
            $this->assertSame('CLIENT-ID', $session->getApiClientId());
            $this->assertNull($session->getOldApiToken());
            $this->assertSame(80, \strlen($session->getCurrentApiToken()));
            $this->assertInstanceOf(DateTime::class, $session->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $session->getRefreshedAt());
            $this->assertSame($session->getCreatedAt()->getTimestamp(), $session->getRefreshedAt()->getTimestamp());
            $this->assertFalse($session->isLocked());
            $this->assertInstanceOf(DateTime::class, $session->getUpdatedAt());
            $this->assertSame($session->getCreatedAt()->getTimestamp(), $session->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateSessionThrowsException(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        $apiClientId = 'CLIENT-ID';

        $request = new Request();
        $request->headers->set(ApiHeaders::API_CLIENT_ID, $apiClientId);

        /** @var RequestStack $requestStack */
        $requestStack = $dic->get(RequestStack::class);
        $requestStack->push($request);

        /** @var ApiTokenGenerator $apiTokenGenerator */
        $apiTokenGenerator = $dic->get(ApiTokenGeneratorWithPredefinedApiToken::class);
        $dic->set(ApiTokenGenerator::class, $apiTokenGenerator);

        /** @var SessionFactory $sessionFactory */
        $sessionFactory = $dic->get(SessionFactoryWithPredefinedApiToken::class);
        $dic->set(SessionFactory::class, $sessionFactory);

        /** @var LoginPersister $loginPersister */
        $loginPersister = $dic->get(LoginPersister::class);

        try {
            $loginPersister->createSession([
                'email' => 'john.doe@example.com',
                'password' => 'secret',
            ]);
            $this->fail('Failed to throw exception.');
        } catch (CouldNotPersistException $e) {
            $data = $e->getData();
            $this->assertSame(25, $data['error']['code']);
            $this->assertSame("Could not generate unique value for 'apiToken' in 5 tries.", $data['error']['message']);
            $this->assertSame("Could not generate unique value for 'apiToken' in 5 tries.", $e->getMessage());
        }
    }
}
