<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\User;
use App\Persisters\IncrementAuthenticationFailuresPersister;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Throwable;

final class IncrementAuthenticationFailuresPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testIncrementAuthenticationFailures(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'hank.doe@example.com';

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $user);
            $this->assertSame(0, $user->getAuthenticationFailures());
            $userUpdatedAt = $user->getUpdatedAt();

            /** @var IncrementAuthenticationFailuresPersister $incrementAuthenticationFailuresPersister */
            $incrementAuthenticationFailuresPersister = $dic->get(IncrementAuthenticationFailuresPersister::class);
            $incrementAuthenticationFailuresPersister->incrementAuthenticationFailures(['email' => $emailAddress]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            $userToCheck = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $userToCheck);
            $this->assertSame(1, $userToCheck->getAuthenticationFailures());
            $this->assertGreaterThan($userUpdatedAt->getTimestamp(), $userToCheck->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
