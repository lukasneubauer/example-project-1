<?php

declare(strict_types=1);

namespace Tests\App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\EntityFactories\SecurityCodeFactory;
use App\Generators\SecurityCodeGenerator;
use DateTime;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SecurityCodeFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        /** @var DateTimeUTC $dateTimeUTC */
        $dateTimeUTC = m::mock(DateTimeUTC::class)
            ->shouldReceive('createDateTimeInstance')
            ->times(1)
            ->andReturn((new DateTimeUTC())->createDateTimeInstance())
            ->getMock();

        /** @var SecurityCodeGenerator $securityCodeGenerator */
        $securityCodeGenerator = m::mock(SecurityCodeGenerator::class)
            ->shouldReceive('generateSecurityCode')
            ->times(1)
            ->andReturn('C50D9XAF6')
            ->getMock();

        $securityCodeFactory = new SecurityCodeFactory($dateTimeUTC, $securityCodeGenerator);

        $securityCode = $securityCodeFactory->create();

        $this->assertSame('C50D9XAF6', $securityCode->getCode());
        $this->assertInstanceOf(DateTime::class, $securityCode->getCreatedAt());
    }

    protected function tearDown(): void
    {
        m::close();
    }
}
