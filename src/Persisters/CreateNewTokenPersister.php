<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Database\UniqueKey;
use App\Entities\User;
use App\EntityFactories\TokenFactory;
use App\Exceptions\CouldNotPersistException;
use App\PersisterErrors\CouldNotGenerateUniqueValue;
use App\Repositories\UserRepository;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;

class CreateNewTokenPersister
{
    /** @var int */
    public const MAX_TRIES = 5;

    /** @var string */
    public const SQL = <<<EOL
UPDATE `users`
SET `token` = ?,
    `token_created_at` = ?,
    `updated_at` = ?
WHERE `id` = ?
EOL;

    private CouldNotGenerateUniqueValue $couldNotGenerateUniqueValue;

    private EntityManager $em;

    private TokenFactory $tokenFactory;

    private UniqueKey $uniqueKey;

    private UserRepository $userRepository;

    public function __construct(
        CouldNotGenerateUniqueValue $couldNotGenerateUniqueValue,
        EntityManager $em,
        TokenFactory $tokenFactory,
        UniqueKey $uniqueKey,
        UserRepository $userRepository
    ) {
        $this->couldNotGenerateUniqueValue = $couldNotGenerateUniqueValue;
        $this->em = $em;
        $this->tokenFactory = $tokenFactory;
        $this->uniqueKey = $uniqueKey;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws CouldNotPersistException
     * @throws DBALDriverException
     * @throws DBALException
     */
    public function createNewToken(array $requestData): User
    {
        $user = $this->userRepository->getByEmail($requestData['email']);
        $user->setToken($this->tokenFactory->create());

        return $this->tryToPersist($user);
    }

    /**
     * @throws CouldNotPersistException
     * @throws DBALDriverException
     * @throws DBALException
     */
    private function tryToPersist(
        User $user,
        int $callTimes = self::MAX_TRIES,
        ?string $uniqueProperty = null
    ): User {
        if ($callTimes === 0) {
            $this->couldNotGenerateUniqueValue->throwException($uniqueProperty, self::MAX_TRIES);
        }

        try {
            $connection = $this->em->getConnection();
            $statement = $connection->prepare(self::SQL);
            $token = $user->getToken();
            $tokenCode = $token->getCode();
            $tokenCreatedAt = $token->getCreatedAt()->format('Y-m-d H:i:s');
            $statement->bindParam(1, $tokenCode);
            $statement->bindParam(2, $tokenCreatedAt);
            $statement->bindParam(3, $tokenCreatedAt);
            $id = $user->getId();
            $statement->bindParam(4, $id);
            $statement->executeQuery();
        } catch (UniqueConstraintViolationException $e) {
            $uniqueKey = $this->uniqueKey->extractUniqueKeyFromExceptionMessage($e->getMessage());
            if ($uniqueKey === User::UNIQUE_KEY_TOKEN) {
                $user->setToken($this->tokenFactory->create());
                $this->tryToPersist($user, $callTimes - 1, 'token');
            }
        }

        return $user;
    }
}
