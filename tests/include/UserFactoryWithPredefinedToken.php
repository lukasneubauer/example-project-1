<?php

declare(strict_types=1);

namespace Tests;

use App\DateTime\DateTimeUTC;
use App\Entities\Password;
use App\Entities\Token;
use App\Entities\User;
use App\EntityFactories\UserFactory;

final class UserFactoryWithPredefinedToken extends UserFactory
{
    public function __construct()
    {
        // mute parent constructor
    }

    public function create(
        string $firstName,
        string $lastName,
        string $email,
        Password $password,
        string $timezone
    ): User {
        $now = (new DateTimeUTC())->createDateTimeInstance();

        return new User(
            \generate_uuid(),
            $firstName,
            $lastName,
            $email,
            $password,
            $timezone,
            new Token((new TokenGeneratorWithPredefinedToken())->generateToken(), (new DateTimeUTC())->createDateTimeInstance()),
            false,
            $now,
            $now
        );
    }
}
