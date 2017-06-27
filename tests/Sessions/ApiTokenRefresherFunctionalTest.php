<?php

declare(strict_types=1);

namespace Tests\App\Sessions;

use App\Repositories\SessionRepository;
use App\Sessions\ApiTokenRefresher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Throwable;

final class ApiTokenRefresherFunctionalTest extends KernelTestCase
{
    public function testRefreshApiTokenIfExpiredUsingNonExpiredCurrentApiToken(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken('4xayt3znz8ri5qc7cc66g9xdu7qmas2ew4q0xd6o6azfvpvroua750gendceih8vpoq19jpljzwvogs6');
            /** @var ApiTokenRefresher $apiTokenRefresher */
            $apiTokenRefresher = $dic->get(ApiTokenRefresher::class);
            $refreshedSession = $apiTokenRefresher->refreshApiTokenIfExpired($session);
            $this->assertSame('ocjku0l06wipcttuxovfhrsltp7tbj91obaxc4arn0fgrehcdymqyo5f2qftb3h1921npl9f4yqr23wy', $refreshedSession->getOldApiToken());
            $this->assertSame('4xayt3znz8ri5qc7cc66g9xdu7qmas2ew4q0xd6o6azfvpvroua750gendceih8vpoq19jpljzwvogs6', $refreshedSession->getCurrentApiToken());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    public function testRefreshApiTokenIfExpiredUsingNonExpiredOldApiToken(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken('ocjku0l06wipcttuxovfhrsltp7tbj91obaxc4arn0fgrehcdymqyo5f2qftb3h1921npl9f4yqr23wy');
            /** @var ApiTokenRefresher $apiTokenRefresher */
            $apiTokenRefresher = $dic->get(ApiTokenRefresher::class);
            $refreshedSession = $apiTokenRefresher->refreshApiTokenIfExpired($session);
            $this->assertSame('ocjku0l06wipcttuxovfhrsltp7tbj91obaxc4arn0fgrehcdymqyo5f2qftb3h1921npl9f4yqr23wy', $refreshedSession->getOldApiToken());
            $this->assertSame('4xayt3znz8ri5qc7cc66g9xdu7qmas2ew4q0xd6o6azfvpvroua750gendceih8vpoq19jpljzwvogs6', $refreshedSession->getCurrentApiToken());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    public function testRefreshApiTokenIfExpiredUsingExpiredCurrentApiToken(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken('py5bjp7s48jgcu9pbtf4a3c7yyazywyhb9si54n8lspkbhozguts0s1ezoy5ohoo3k8az8u59vt5gj4r');
            /** @var ApiTokenRefresher $apiTokenRefresher */
            $apiTokenRefresher = $dic->get(ApiTokenRefresher::class);
            $refreshedSession = $apiTokenRefresher->refreshApiTokenIfExpired($session);
            $this->assertSame('py5bjp7s48jgcu9pbtf4a3c7yyazywyhb9si54n8lspkbhozguts0s1ezoy5ohoo3k8az8u59vt5gj4r', $refreshedSession->getOldApiToken());
            $this->assertNotSame('py5bjp7s48jgcu9pbtf4a3c7yyazywyhb9si54n8lspkbhozguts0s1ezoy5ohoo3k8az8u59vt5gj4r', $refreshedSession->getCurrentApiToken());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
