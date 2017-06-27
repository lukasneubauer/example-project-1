<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Token;
use App\Entities\User;
use App\Exceptions\CouldNotPersistException;
use App\Generators\TokenGenerator;
use App\Persisters\CreateNewTokenPersister;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Tests\TokenGeneratorWithPredefinedToken;
use Throwable;

final class CreateNewTokenPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateNewToken(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $emailAddress = 'john.doe@example.com';
            $expiredToken = 'xvizlczgexbhr404ud0k';

            /** @var CreateNewTokenPersister $createNewTokenPersister */
            $createNewTokenPersister = $dic->get(CreateNewTokenPersister::class);
            $createNewTokenPersister->createNewToken(['email' => $emailAddress]);

            EntityManagerCleanup::cleanupEntityManager($dic);

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);
            $user = $userRepository->getByEmail($emailAddress);
            $this->assertInstanceOf(User::class, $user);
            $token = $user->getToken();
            $this->assertInstanceOf(Token::class, $token);
            $this->assertTrue(\strlen($token->getCode()) === 20);
            $this->assertNotSame($expiredToken, $token->getCode());
            $this->assertSame($user->getUpdatedAt()->getTimestamp(), $token->getCreatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateNewTokenThrowsException(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        $emailAddress = 'nora.doe@example.com';

        /** @var TokenGenerator $tokenGenerator */
        $tokenGenerator = $dic->get(TokenGeneratorWithPredefinedToken::class);
        $dic->set(TokenGenerator::class, $tokenGenerator);

        /** @var CreateNewTokenPersister $createNewTokenPersister */
        $createNewTokenPersister = $dic->get(CreateNewTokenPersister::class);

        try {
            $createNewTokenPersister->createNewToken(['email' => $emailAddress]);
            $this->fail('Failed to throw exception.');
        } catch (CouldNotPersistException $e) {
            $data = $e->getData();
            $this->assertSame(25, $data['error']['code']);
            $this->assertSame("Could not generate unique value for 'token' in 5 tries.", $data['error']['message']);
            $this->assertSame("Could not generate unique value for 'token' in 5 tries.", $e->getMessage());
        }
    }
}
