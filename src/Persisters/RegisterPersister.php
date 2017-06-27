<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Database\UniqueKey;
use App\Entities\User;
use App\EntityFactories\TokenFactory;
use App\EntityFactories\UserFactory;
use App\Exceptions\CouldNotPersistException;
use App\Exceptions\PasswordHashingFailedException;
use App\Passwords\PasswordEncoderEntityFactory;
use App\PersisterErrors\CouldNotGenerateUniqueValue;
use App\PersisterErrors\ValueIsAlreadyTaken;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;

class RegisterPersister
{
    /** @var int */
    public const MAX_TRIES = 5;

    /** @var string */
    public const SQL = <<<EOL
INSERT INTO `users` (
    `id`,
    `first_name`,
    `last_name`,
    `email`,
    `password_hash`,
    `password_algorithm`,
    `is_teacher`,
    `is_student`,
    `timezone`,
    `token`,
    `token_created_at`,
    `security_code`,
    `security_code_created_at`,
    `security_code_failures`,
    `authentication_failures`,
    `is_locked`,
    `is_active`,
    `created_at`,
    `updated_at`
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
EOL;

    private CouldNotGenerateUniqueValue $couldNotGenerateUniqueValue;

    private EntityManager $em;

    private PasswordEncoderEntityFactory $passwordEncoderEntityFactory;

    private TokenFactory $tokenFactory;

    private UniqueKey $uniqueKey;

    private UserFactory $userFactory;

    private ValueIsAlreadyTaken $valueIsAlreadyTaken;

    public function __construct(
        CouldNotGenerateUniqueValue $couldNotGenerateUniqueValue,
        EntityManager $em,
        PasswordEncoderEntityFactory $passwordEncoderEntityFactory,
        TokenFactory $tokenFactory,
        UniqueKey $uniqueKey,
        UserFactory $userFactory,
        ValueIsAlreadyTaken $valueIsAlreadyTaken
    ) {
        $this->couldNotGenerateUniqueValue = $couldNotGenerateUniqueValue;
        $this->em = $em;
        $this->passwordEncoderEntityFactory = $passwordEncoderEntityFactory;
        $this->tokenFactory = $tokenFactory;
        $this->uniqueKey = $uniqueKey;
        $this->userFactory = $userFactory;
        $this->valueIsAlreadyTaken = $valueIsAlreadyTaken;
    }

    /**
     * @throws CouldNotPersistException
     * @throws DBALDriverException
     * @throws DBALException
     * @throws PasswordHashingFailedException
     */
    public function createUser(array $requestData): User
    {
        $password = $this->passwordEncoderEntityFactory->createPassword($requestData['password']);
        $user = $this->userFactory->create(
            $requestData['firstName'],
            $requestData['lastName'],
            $requestData['email'],
            $password,
            $requestData['timezone']
        );

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
            $id = $user->getId();
            $statement->bindParam(1, $id);
            $firstName = $user->getFirstName();
            $statement->bindParam(2, $firstName);
            $lastName = $user->getLastName();
            $statement->bindParam(3, $lastName);
            $email = $user->getEmail();
            $statement->bindParam(4, $email);
            $passwordHash = $user->getPassword()->getHash();
            $statement->bindParam(5, $passwordHash);
            $passwordAlgorithm = $user->getPassword()->getAlgorithm();
            $statement->bindParam(6, $passwordAlgorithm);
            $isTeacher = (int) $user->isTeacher();
            $statement->bindParam(7, $isTeacher);
            $isStudent = (int) $user->isStudent();
            $statement->bindParam(8, $isStudent);
            $timezone = $user->getTimezone();
            $statement->bindParam(9, $timezone);
            $token = $user->getToken();
            $tokenCode = $token->getCode();
            $tokenCreatedAt = $token->getCreatedAt()->format('Y-m-d H:i:s');
            $statement->bindParam(10, $tokenCode);
            $statement->bindParam(11, $tokenCreatedAt);
            $securityCodeCode = null;
            $securityCodeCreatedAt = null;
            $securityCodeInputFailures = 0;
            $statement->bindParam(12, $securityCodeCode);
            $statement->bindParam(13, $securityCodeCreatedAt);
            $statement->bindParam(14, $securityCodeInputFailures);
            $authenticationFailures = $user->getAuthenticationFailures();
            $statement->bindParam(15, $authenticationFailures);
            $isLocked = (int) $user->isLocked();
            $statement->bindParam(16, $isLocked);
            $isActive = (int) $user->isActive();
            $statement->bindParam(17, $isActive);
            $createdAt = $user->getCreatedAt()->format('Y-m-d H:i:s');
            $statement->bindParam(18, $createdAt);
            $updatedAt = $user->getUpdatedAt()->format('Y-m-d H:i:s');
            $statement->bindParam(19, $updatedAt);
            $statement->executeQuery();
        } catch (UniqueConstraintViolationException $e) {
            $uniqueKey = $this->uniqueKey->extractUniqueKeyFromExceptionMessage($e->getMessage());
            if ($uniqueKey === User::UNIQUE_KEY_EMAIL) {
                $this->valueIsAlreadyTaken->throwException('email');
            }
            if ($uniqueKey === User::UNIQUE_KEY_TOKEN) {
                $user->setToken($this->tokenFactory->create());
                $this->tryToPersist($user, $callTimes - 1, 'token');
            }
        }

        return $user;
    }
}
