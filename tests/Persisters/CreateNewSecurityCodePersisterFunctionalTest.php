<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\SecurityCode;
use App\Entities\User;
use App\Persisters\CreateNewSecurityCodePersister;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Throwable;

final class CreateNewSecurityCodePersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateNewSecurityCode(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'seth.doe@example.com';

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $user);
            $oldSecurityCode = $user->getSecurityCode();
            $this->assertInstanceOf(SecurityCode::class, $oldSecurityCode);
            $oldSecurityCodeCreatedAt = $oldSecurityCode->getCreatedAt();

            /** @var CreateNewSecurityCodePersister $createNewSecurityCodePersister */
            $createNewSecurityCodePersister = $dic->get(CreateNewSecurityCodePersister::class);
            $createNewSecurityCodePersister->createNewSecurityCode(['email' => $emailAddress]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            $userToCheck = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $userToCheck);
            $newSecurityCode = $userToCheck->getSecurityCode();
            $this->assertInstanceOf(SecurityCode::class, $newSecurityCode);
            $this->assertSame(0, $newSecurityCode->getInputFailures());
            $this->assertTrue(\strlen($newSecurityCode->getCode()) === 9);
            $this->assertNotSame($oldSecurityCode->getCode(), $newSecurityCode->getCode());
            $this->assertGreaterThan(
                $oldSecurityCodeCreatedAt->getTimestamp(),
                $newSecurityCode->getCreatedAt()->getTimestamp()
            );
            $this->assertSame(
                $userToCheck->getUpdatedAt()->getTimestamp(),
                $newSecurityCode->getCreatedAt()->getTimestamp()
            );
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
