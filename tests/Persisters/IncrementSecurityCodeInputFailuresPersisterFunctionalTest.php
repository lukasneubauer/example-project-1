<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\SecurityCode;
use App\Entities\User;
use App\Persisters\IncrementSecurityCodeInputFailuresPersister;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Throwable;

final class IncrementSecurityCodeInputFailuresPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testIncrementSecurityCodeInputFailures(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'nina.doe@example.com';

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $user);
            $securityCode = $user->getSecurityCode();
            $this->assertInstanceOf(SecurityCode::class, $securityCode);
            $this->assertSame(0, $securityCode->getInputFailures());
            $userUpdatedAt = $user->getUpdatedAt();

            /** @var IncrementSecurityCodeInputFailuresPersister $incrementSecurityCodeInputFailuresPersister */
            $incrementSecurityCodeInputFailuresPersister = $dic->get(IncrementSecurityCodeInputFailuresPersister::class);
            $incrementSecurityCodeInputFailuresPersister->incrementSecurityCodeInputFailures(['email' => $emailAddress]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            $userToCheck = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $userToCheck);
            $securityCodeToCheck = $userToCheck->getSecurityCode();
            $this->assertInstanceOf(SecurityCode::class, $securityCodeToCheck);
            $this->assertSame(1, $securityCodeToCheck->getInputFailures());
            $this->assertGreaterThan($userUpdatedAt->getTimestamp(), $userToCheck->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
