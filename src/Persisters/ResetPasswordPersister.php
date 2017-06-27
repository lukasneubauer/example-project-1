<?php

declare(strict_types=1);

namespace App\Persisters;

use App\DateTime\DateTimeUTC;
use App\Entities\User;
use App\Exceptions\PasswordHashingFailedException;
use App\Passwords\PasswordEncoderEntityFactory;
use App\Repositories\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class ResetPasswordPersister
{
    private DateTimeUTC $dateTimeUTC;

    private EntityManager $em;

    private PasswordEncoderEntityFactory $passwordEncoderEntityFactory;

    private UserRepository $userRepository;

    public function __construct(
        DateTimeUTC $dateTimeUTC,
        EntityManager $em,
        PasswordEncoderEntityFactory $passwordEncoderEntityFactory,
        UserRepository $userRepository
    ) {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->em = $em;
        $this->passwordEncoderEntityFactory = $passwordEncoderEntityFactory;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PasswordHashingFailedException
     */
    public function resetPassword(array $requestData): User
    {
        $userId = $requestData['userId'];
        $user = $this->userRepository->getById($userId);
        $password = $this->passwordEncoderEntityFactory->createPassword($requestData['password']);
        $user->setPassword($password);
        $user->unsetToken();
        $user->setUpdatedAt($this->dateTimeUTC->createDateTimeInstance());
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
