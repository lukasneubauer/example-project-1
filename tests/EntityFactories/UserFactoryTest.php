<?php

declare(strict_types=1);

namespace Tests\App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Password;
use App\Entities\Token;
use App\EntityFactories\TokenFactory;
use App\EntityFactories\UserFactory;
use App\Generators\UuidGenerator;
use App\Passwords\PasswordAlgorithms;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class UserFactoryTest extends TestCase
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

        $token = m::mock(Token::class);
        $token->shouldReceive('isEmpty')
            ->times(4)
            ->andReturn(false);
        $token->shouldReceive('getCode')
            ->times(1)
            ->andReturn('014boffm55rr2goahorn');
        $token->shouldReceive('getCreatedAt')
            ->times(2)
            ->andReturn((new DateTimeUTC())->createDateTimeInstance('1970-01-01 00:00:00'));

        /** @var TokenFactory $tokenFactory */
        $tokenFactory = m::mock(TokenFactory::class)
            ->shouldReceive('create')
            ->times(1)
            ->andReturn($token)
            ->getMock();

        $userFactory = new UserFactory($dateTimeUTC, $uuidGenerator, $tokenFactory);

        $user = $userFactory->create(
            'John',
            'Doe',
            'john.doe@example.com',
            new Password('$2y$13$Y9rdI88aSRnmbjZCwDJqSui/RGvzJYFGezxXVgI/tsaGJCk8GYmaG', PasswordAlgorithms::BCRYPT),
            'Europe/Prague'
        );

        $this->assertSame('24887dd6-df68-4938-a4a1-49c6401a0389', $user->getId());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe@example.com', $user->getEmail());
        $this->assertSame('$2y$13$Y9rdI88aSRnmbjZCwDJqSui/RGvzJYFGezxXVgI/tsaGJCk8GYmaG', $user->getPassword()->getHash());
        $this->assertSame(PasswordAlgorithms::BCRYPT, $user->getPassword()->getAlgorithm());
        $this->assertFalse($user->isTeacher());
        $this->assertFalse($user->isStudent());
        $this->assertSame('Europe/Prague', $user->getTimezone());
        $this->assertInstanceOf(Token::class, $user->getToken());
        $this->assertSame('014boffm55rr2goahorn', $user->getToken()->getCode());
        $this->assertInstanceOf(DateTime::class, $user->getToken()->getCreatedAt());
        $this->assertSame('1970-01-01 00:00:00', $user->getToken()->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertNull($user->getSecurityCode());
        $this->assertFalse($user->isActive());
        $this->assertInstanceOf(DateTime::class, $user->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $user->getUpdatedAt());
        $this->assertInstanceOf(ArrayCollection::class, $user->getTeacherCourses());
        $this->assertInstanceOf(ArrayCollection::class, $user->getStudentCourses());
    }

    protected function tearDown(): void
    {
        m::close();
    }
}
