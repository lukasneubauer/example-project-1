<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Entities\User;
use App\EntityFactories\TokenFactory;
use App\Repositories\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class RequestEmailToResetPasswordPersister
{
    private EntityManager $em;

    private TokenFactory $tokenFactory;

    private UserRepository $userRepository;

    public function __construct(
        EntityManager $em,
        TokenFactory $tokenFactory,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->tokenFactory = $tokenFactory;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function requestEmailToResetPassword(array $requestData): User
    {
        $user = $this->userRepository->getByEmail($requestData['email']);
        $token = $this->tokenFactory->create();
        $user->setToken($token);
        $user->setUpdatedAt($token->getCreatedAt());
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
