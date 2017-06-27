<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Password;
use App\Entities\Token;
use App\EntityFactories\UserFactory;
use App\Exceptions\CouldNotPersistException;
use App\Generators\TokenGenerator;
use App\Passwords\PasswordAlgorithms;
use App\Persisters\RegisterPersister;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Tests\TokenGeneratorWithPredefinedToken;
use Tests\UserFactoryWithPredefinedToken;
use Throwable;

final class RegisterPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateUser(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            /** @var RegisterPersister $registerPersister */
            $registerPersister = $dic->get(RegisterPersister::class);

            $requestData = [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'extra-new-john-doe@example.com',
                'password' => 'secret',
                'timezone' => 'Europe/Prague',
            ];

            $newUser = $registerPersister->createUser($requestData);
            $this->assertSame(36, \strlen($newUser->getId()));
            $this->assertSame('John', $newUser->getFirstName());
            $this->assertSame('Doe', $newUser->getLastName());
            $this->assertSame('extra-new-john-doe@example.com', $newUser->getEmail());
            $this->assertInstanceOf(Password::class, $newUser->getPassword());
            $this->assertTrue(\is_string($newUser->getPassword()->getHash()));
            $this->assertSame(60, \strlen($newUser->getPassword()->getHash()));
            $this->assertStringStartsWith('$2y$13$', $newUser->getPassword()->getHash());
            $this->assertSame(PasswordAlgorithms::BCRYPT, $newUser->getPassword()->getAlgorithm());
            $this->assertFalse($newUser->isTeacher());
            $this->assertFalse($newUser->isStudent());
            $this->assertSame('Europe/Prague', $newUser->getTimezone());
            $this->assertInstanceOf(Token::class, $newUser->getToken());
            $this->assertTrue(\is_string($newUser->getToken()->getCode()));
            $this->assertSame(20, \strlen($newUser->getToken()->getCode()));
            $this->assertInstanceOf(DateTime::class, $newUser->getToken()->getCreatedAt());
            $this->assertNull($newUser->getSecurityCode());
            $this->assertSame(0, $newUser->getAuthenticationFailures());
            $this->assertFalse($newUser->isLocked());
            $this->assertFalse($newUser->isActive());
            $this->assertInstanceOf(DateTime::class, $newUser->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $newUser->getUpdatedAt());
            $this->assertSame($newUser->getCreatedAt()->getTimestamp(), $newUser->getToken()->getCreatedAt()->getTimestamp());
            $this->assertSame($newUser->getCreatedAt()->getTimestamp(), $newUser->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Collection::class, $newUser->getTeacherCourses());
            $this->assertCount(0, $newUser->getTeacherCourses());
            $this->assertInstanceOf(Collection::class, $newUser->getStudentCourses());
            $this->assertCount(0, $newUser->getStudentCourses());
            $this->assertInstanceOf(Collection::class, $newUser->getSessions());
            $this->assertCount(0, $newUser->getSessions());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateUserThrowsExceptionBecauseOfDuplicityInEmail(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        /** @var RegisterPersister $registerPersister */
        $registerPersister = $dic->get(RegisterPersister::class);

        $requestData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'secret',
            'timezone' => 'Europe/Prague',
        ];

        try {
            $registerPersister->createUser($requestData);
            $this->fail('Failed to throw exception.');
        } catch (CouldNotPersistException $e) {
            $data = $e->getData();
            $this->assertSame(14, $data['error']['code']);
            $this->assertSame("Value for 'email' in request body is already taken.", $data['error']['message']);
            $this->assertSame("Value for 'email' in request body is already taken.", $e->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateUserThrowsExceptionBecauseOfDuplicityInToken(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        /** @var TokenGenerator $tokenGenerator */
        $tokenGenerator = $dic->get(TokenGeneratorWithPredefinedToken::class);
        $dic->set(TokenGenerator::class, $tokenGenerator);

        /** @var UserFactory $userFactory */
        $userFactory = $dic->get(UserFactoryWithPredefinedToken::class);
        $dic->set(UserFactory::class, $userFactory);

        /** @var RegisterPersister $registerPersister */
        $registerPersister = $dic->get(RegisterPersister::class);

        $requestData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'extra-new-john-doe@example.com',
            'password' => 'secret',
            'timezone' => 'Europe/Prague',
        ];

        try {
            $registerPersister->createUser($requestData);
            $this->fail('Failed to throw exception.');
        } catch (CouldNotPersistException $e) {
            $data = $e->getData();
            $this->assertSame(25, $data['error']['code']);
            $this->assertSame("Could not generate unique value for 'token' in 5 tries.", $data['error']['message']);
            $this->assertSame("Could not generate unique value for 'token' in 5 tries.", $e->getMessage());
        }
    }
}
