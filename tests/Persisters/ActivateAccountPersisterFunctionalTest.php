<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Token;
use App\Entities\User;
use App\Persisters\ActivateAccountPersister;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Throwable;

final class ActivateAccountPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testActivateAccount(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'jake.doe@example.com';

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $user);
            $token = $user->getToken();
            $this->assertInstanceOf(Token::class, $token);
            $this->assertTrue(\strlen($token->getCode()) === 20);
            $this->assertFalse($user->isActive());
            $userUpdatedAt = $user->getUpdatedAt();

            /** @var ActivateAccountPersister $activateAccountPersister */
            $activateAccountPersister = $dic->get(ActivateAccountPersister::class);
            $activateAccountPersister->activateAccount(['email' => $emailAddress]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            $userToCheck = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $userToCheck);
            $this->assertNull($userToCheck->getToken());
            $this->assertTrue($userToCheck->isActive());
            $this->assertGreaterThan(
                $userUpdatedAt->getTimestamp(),
                $userToCheck->getUpdatedAt()->getTimestamp()
            );
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
