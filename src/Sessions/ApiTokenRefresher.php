<?php

declare(strict_types=1);

namespace App\Sessions;

use App\DateTime\DateTimeUTC;
use App\Entities\Session;
use App\Generators\ApiTokenGenerator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class ApiTokenRefresher
{
    private ApiTokenGenerator $apiTokenGenerator;

    private DateTimeUTC $dateTimeUTC;

    private EntityManager $em;

    public function __construct(
        ApiTokenGenerator $apiTokenGenerator,
        DateTimeUTC $dateTimeUTC,
        EntityManager $em
    ) {
        $this->apiTokenGenerator = $apiTokenGenerator;
        $this->dateTimeUTC = $dateTimeUTC;
        $this->em = $em;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function refreshApiTokenIfExpired(Session $session): Session
    {
        $lastUpdate = $session->getRefreshedAt()->getTimestamp();
        $expiration = $lastUpdate + Session::CURRENT_API_TOKEN_EXPIRATION_IN_SECONDS;

        if (\time() >= $expiration) {
            $session->setOldApiToken($session->getCurrentApiToken());
            $session->setCurrentApiToken($this->apiTokenGenerator->generateApiToken());

            $now = $this->dateTimeUTC->createDateTimeInstance();
            $session->setRefreshedAt($now);
            $session->setUpdatedAt($now);
            $this->em->persist($session);
            $this->em->flush();
        }

        return $session;
    }
}
