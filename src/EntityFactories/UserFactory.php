<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Password;
use App\Entities\User;
use App\Generators\UuidGenerator;

class UserFactory
{
    private DateTimeUTC $dateTimeUTC;

    private UuidGenerator $uuidGenerator;

    private TokenFactory $tokenFactory;

    public function __construct(
        DateTimeUTC $dateTimeUTC,
        UuidGenerator $uuidGenerator,
        TokenFactory $tokenFactory
    ) {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->uuidGenerator = $uuidGenerator;
        $this->tokenFactory = $tokenFactory;
    }

    public function create(
        string $firstName,
        string $lastName,
        string $email,
        Password $password,
        string $timezone
    ): User {
        $now = $this->dateTimeUTC->createDateTimeInstance();

        return new User(
            $this->uuidGenerator->generateUuid(),
            $firstName,
            $lastName,
            $email,
            $password,
            $timezone,
            $this->tokenFactory->create(),
            false,
            $now,
            $now
        );
    }
}
