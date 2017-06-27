<?php

declare(strict_types=1);

namespace Tests\App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Subject;
use App\Entities\User;
use App\EntityFactories\CourseFactory;
use App\Generators\UuidGenerator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CourseFactoryTest extends TestCase
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

        $courseFactory = new CourseFactory($dateTimeUTC, $uuidGenerator);

        /** @var Subject $subject */
        $subject = m::mock(Subject::class);

        /** @var User $user */
        $user = m::mock(User::class);

        $course = $courseFactory->create(
            $subject,
            $user,
            'Lorem ipsum',
            25000
        );

        $this->assertSame('24887dd6-df68-4938-a4a1-49c6401a0389', $course->getId());
        $this->assertInstanceOf(Subject::class, $course->getSubject());
        $this->assertInstanceOf(User::class, $course->getTeacher());
        $this->assertSame('Lorem ipsum', $course->getName());
        $this->assertSame(25000, $course->getPrice());
        $this->assertInstanceOf(DateTime::class, $course->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $course->getUpdatedAt());
        $this->assertInstanceOf(ArrayCollection::class, $course->getStudents());
        $this->assertInstanceOf(ArrayCollection::class, $course->getLessons());
    }

    protected function tearDown(): void
    {
        m::close();
    }
}
