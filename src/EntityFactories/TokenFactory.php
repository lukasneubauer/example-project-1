<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Token;
use App\Generators\TokenGenerator;

class TokenFactory
{
    private DateTimeUTC $dateTimeUTC;

    private TokenGenerator $tokenGenerator;

    public function __construct(DateTimeUTC $dateTimeUTC, TokenGenerator $tokenGenerator)
    {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function create(): Token
    {
        return new Token($this->tokenGenerator->generateToken(), $this->dateTimeUTC->createDateTimeInstance());
    }
}
