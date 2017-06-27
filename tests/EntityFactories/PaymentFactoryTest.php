<?php

declare(strict_types=1);

namespace Tests\App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Course;
use App\Entities\User;
use App\EntityFactories\PaymentFactory;
use App\Generators\UuidGenerator;
use DateTime;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PaymentFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        /** @var DateTimeUTC $dateTimeUTC */
        $dateTimeUTC = m::mock(DateTimeUTC::class)
            ->shouldReceive('createDateTimeInstance')
            ->times(1)
            ->andReturn((new DateTimeUTC())->createDateTimeInstance())
            ->getMock();

        /** @var UuidGenerator $uuidGenerator */
        $uuidGenerator = m::mock(UuidGenerator::class)
            ->shouldReceive('generateUuid')
            ->times(1)
            ->andReturn('24887dd6-df68-4938-a4a1-49c6401a0389')
            ->getMock();

        $paymentFactory = new PaymentFactory($dateTimeUTC, $uuidGenerator);

        /** @var Course $course */
        $course = m::mock(Course::class);

        /** @var User $user */
        $user = m::mock(User::class);

        $payment = $paymentFactory->create(
            $course,
            $user,
            25000
        );

        $this->assertSame('24887dd6-df68-4938-a4a1-49c6401a0389', $payment->getId());
        $this->assertInstanceOf(Course::class, $payment->getCourse());
        $this->assertInstanceOf(User::class, $payment->getStudent());
        $this->assertSame(25000, $payment->getPrice());
        $this->assertInstanceOf(DateTime::class, $payment->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $payment->getUpdatedAt());
    }

    protected function tearDown(): void
    {
        m::close();
    }
}
