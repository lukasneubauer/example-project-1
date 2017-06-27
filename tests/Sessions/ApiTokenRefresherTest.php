<?php

declare(strict_types=1);

namespace Tests\App\Sessions;

use App\DateTime\DateTimeUTC;
use App\Entities\Session;
use App\Generators\ApiTokenGenerator;
use App\Sessions\ApiTokenRefresher;
use DateTime;
use Doctrine\ORM\EntityManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

final class ApiTokenRefresherTest extends TestCase
{
    /**
     * @dataProvider getTimestampsThatAreNotExpiredYet
     */
    public function testRefreshApiTokenIfExpiredNotExpiredYet(int $expiration): void
    {
        $refreshedAt = m::mock(DateTime::class)
            ->shouldReceive('getTimestamp')
            ->times(1)
            ->andReturn(\time() - $expiration)
            ->getMock();

        $session = m::mock(Session::class);
        $session->shouldReceive('getRefreshedAt')
            ->times(1)
            ->andReturn($refreshedAt);

        $apiTokenGenerator = m::mock(ApiTokenGenerator::class);

        $dateTimeUTC = m::mock(DateTimeUTC::class);

        $em = m::mock(EntityManager::class);

        $apiTokenRefresher = new ApiTokenRefresher($apiTokenGenerator, $dateTimeUTC, $em);
        $refreshedSession = $apiTokenRefresher->refreshApiTokenIfExpired($session);

        $this->assertInstanceOf(Session::class, $refreshedSession);
    }

    public function getTimestampsThatAreNotExpiredYet(): array
    {
        return [
            [899],
            [0],
        ];
    }

    /**
     * @dataProvider getTimestampsThatAreAlreadyExpired
     */
    public function testRefreshApiTokenIfExpiredAlreadyExpired(int $expiration): void
    {
        $refreshedAt = m::mock(DateTime::class)
            ->shouldReceive('getTimestamp')
            ->times(1)
            ->andReturn(\time() - $expiration)
            ->getMock();

        $session = m::mock(Session::class);
        $session->shouldReceive('getRefreshedAt')
            ->times(1)
            ->andReturn($refreshedAt);
        $session->shouldReceive('getCurrentApiToken')
            ->times(1)
            ->andReturn('7nytzn7u38iswoc5yu8r0262h9c0a2qrq6gyi217qhabhqjr193ts0uoa7dgb9qzuvi4thasx5upejxh');
        $session->shouldReceive('setOldApiToken')
            ->times(1)
            ->andReturn($session);
        $session->shouldReceive('setCurrentApiToken')
            ->times(1)
            ->andReturn($session);
        $session->shouldReceive('setRefreshedAt')
            ->times(1)
            ->andReturn($session);
        $session->shouldReceive('setUpdatedAt')
            ->times(1)
            ->andReturn($session);

        $em = m::mock(EntityManager::class);
        $em->shouldReceive('persist')
            ->times(1)
            ->andReturnUndefined();
        $em->shouldReceive('flush')
            ->times(1)
            ->andReturnUndefined();

        $apiTokenGenerator = m::mock(ApiTokenGenerator::class)
            ->shouldReceive('generateApiToken')
            ->times(1)
            ->andReturn('5rx5spaajz99luul3eb61v9i8kt0bhovtlzwtgbvwih4fw4sotqtvjge00mxp451rfz9e27ecavwloi7')
            ->getMock();

        $dateTimeUTC = m::mock(DateTimeUTC::class)
            ->shouldReceive('createDateTimeInstance')
            ->times(1)
            ->andReturn((new DateTimeUTC())->createDateTimeInstance())
            ->getMock();

        $apiTokenRefresher = new ApiTokenRefresher($apiTokenGenerator, $dateTimeUTC, $em);
        $refreshedSession = $apiTokenRefresher->refreshApiTokenIfExpired($session);

        $this->assertInstanceOf(Session::class, $refreshedSession);
    }

    public function getTimestampsThatAreAlreadyExpired(): array
    {
        return [
            [900],
            [901],
        ];
    }

    protected function tearDown(): void
    {
        m::close();
    }
}
