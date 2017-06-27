<?php

declare(strict_types=1);

namespace Tests;

use App\DateTime\DateTimeUTC;
use App\Entities\Session;
use App\Entities\User;
use App\EntityFactories\SessionFactory;

final class SessionFactoryWithPredefinedApiToken extends SessionFactory
{
    public function __construct()
    {
        // mute parent constructor
    }

    public function create(
        User $user,
        string $apiClientId
    ): Session {
        $now = (new DateTimeUTC())->createDateTimeInstance();

        return new Session(
            \generate_uuid(),
            $user,
            $apiClientId,
            (new ApiTokenGeneratorWithPredefinedApiToken())->generateApiToken(),
            $now,
            $now,
            $now
        );
    }
}
