<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Session;
use App\Entities\User;
use App\Generators\ApiTokenGenerator;
use App\Generators\UuidGenerator;

class SessionFactory
{
    private DateTimeUTC $dateTimeUTC;

    private UuidGenerator $uuidGenerator;

    private ApiTokenGenerator $apiTokenGenerator;

    public function __construct(
        DateTimeUTC $dateTimeUTC,
        UuidGenerator $uuidGenerator,
        ApiTokenGenerator $apiTokenGenerator
    ) {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->uuidGenerator = $uuidGenerator;
        $this->apiTokenGenerator = $apiTokenGenerator;
    }

    public function create(
        User $user,
        string $apiClientId
    ): Session {
        $now = $this->dateTimeUTC->createDateTimeInstance();

        return new Session(
            $this->uuidGenerator->generateUuid(),
            $user,
            $apiClientId,
            $this->apiTokenGenerator->generateApiToken(),
            $now,
            $now,
            $now
        );
    }
}
