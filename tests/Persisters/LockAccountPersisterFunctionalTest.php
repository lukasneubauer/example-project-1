<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\User;
use App\Persisters\LockAccountPersister;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Throwable;

final class LockAccountPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testLockAccount(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'zack.doe@example.com';

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $user);
            $this->assertSame(2, $user->getAuthenticationFailures());
            $this->assertFalse($user->isLocked());
            $userUpdatedAt = $user->getUpdatedAt();

            /** @var LockAccountPersister $lockAccountPersister */
            $lockAccountPersister = $dic->get(LockAccountPersister::class);
            $lockAccountPersister->lockAccount(['email' => $emailAddress]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            $userToCheck = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $userToCheck);
            $this->assertSame(3, $userToCheck->getAuthenticationFailures());
            $this->assertTrue($userToCheck->isLocked());
            $this->assertGreaterThan($userUpdatedAt->getTimestamp(), $userToCheck->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
