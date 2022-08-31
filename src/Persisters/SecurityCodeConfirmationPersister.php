<?php

declare(strict_types=1);

namespace App\Persisters;

use App\DateTime\DateTimeUTC;
use App\Entities\User;
use App\Repositories\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class SecurityCodeConfirmationPersister
{
    private DateTimeUTC $dateTimeUTC;

    private EntityManager $em;

    private UserRepository $userRepository;

    public function __construct(
        DateTimeUTC $dateTimeUTC,
        EntityManager $em,
        UserRepository $userRepository
    ) {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function confirmSecurityCode(array $requestData): User
    {
        $user = $this->userRepository->getByEmail($requestData['email']);
        $user->unsetSecurityCode();
        $user->setAuthenticationFailures(0);
        $user->setIsLocked(false);
        $user->setUpdatedAt($this->dateTimeUTC->createDateTimeInstance());
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
