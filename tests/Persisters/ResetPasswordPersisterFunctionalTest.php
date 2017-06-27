<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Password;
use App\Passwords\PasswordAlgorithms;
use App\Persisters\ResetPasswordPersister;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Throwable;

final class ResetPasswordPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testResetPassword(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'jane.doe@example.com';

            $password = 'new-secret';

            $requestData = [
                'userId' => '912ff62e-fef5-442a-9953-b7c18dca9dae',
                'password' => $password,
            ];

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $user = $userRepository->getByEmail($emailAddress);
            $oldPassword = $user->getPassword();
            $userUpdatedAt = $user->getUpdatedAt();

            /** @var ResetPasswordPersister $resetPasswordPersister */
            $resetPasswordPersister = $dic->get(ResetPasswordPersister::class);
            $resetPasswordPersister->resetPassword($requestData);

            $userToCheck = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(Password::class, $userToCheck->getPassword());
            $this->assertTrue(\is_string($userToCheck->getPassword()->getHash()));
            $this->assertSame(60, \strlen($userToCheck->getPassword()->getHash()));
            $this->assertStringStartsWith('$2y$13$', $userToCheck->getPassword()->getHash());
            $this->assertSame(PasswordAlgorithms::BCRYPT, $userToCheck->getPassword()->getAlgorithm());
            $this->assertTrue(\password_verify($password, $userToCheck->getPassword()->getHash()));
            $this->assertNotSame($oldPassword->getHash(), $userToCheck->getPassword()->getHash());
            $this->assertNull($userToCheck->getToken());
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
