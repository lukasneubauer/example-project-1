<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Token;
use App\Persisters\RequestEmailToResetPasswordPersister;
use App\Repositories\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Throwable;

final class RequestEmailToResetPasswordPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testRequestEmailToResetPassword(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'john.doe@example.com';

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(Token::class, $user->getToken());
            $tokenBeforeEmailWasSent = $user->getToken();
            $this->assertInstanceOf(DateTime::class, $user->getUpdatedAt());
            $updatedAtBeforeEmailWasSent = $user->getUpdatedAt();

            /** @var RequestEmailToResetPasswordPersister $requestEmailToResetPasswordPersister */
            $requestEmailToResetPasswordPersister = $dic->get(RequestEmailToResetPasswordPersister::class);
            $requestEmailToResetPasswordPersister->requestEmailToResetPassword(['email' => $emailAddress]);

            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(Token::class, $user->getToken());
            $this->assertNotSame($tokenBeforeEmailWasSent->getCode(), $user->getToken()->getCode());
            $this->assertInstanceOf(DateTime::class, $user->getUpdatedAt());
            $this->assertGreaterThan($updatedAtBeforeEmailWasSent->getTimestamp(), $user->getUpdatedAt()->getTimestamp());
            $this->assertSame($user->getUpdatedAt()->getTimestamp(), $user->getToken()->getCreatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
