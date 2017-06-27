<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Entities\User;
use App\EntityFactories\SecurityCodeFactory;
use App\Repositories\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class CreateNewSecurityCodePersister
{
    private EntityManager $em;

    private SecurityCodeFactory $securityCodeFactory;

    private UserRepository $userRepository;

    public function __construct(
        EntityManager $em,
        SecurityCodeFactory $securityCodeFactory,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->securityCodeFactory = $securityCodeFactory;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createNewSecurityCode(array $requestData): User
    {
        $user = $this->userRepository->getByEmail($requestData['email']);
        $securityCode = $this->securityCodeFactory->create();
        $user->setSecurityCode($securityCode);
        $user->setUpdatedAt($securityCode->getCreatedAt());
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
