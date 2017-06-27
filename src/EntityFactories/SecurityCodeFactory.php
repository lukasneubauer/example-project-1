<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\SecurityCode;
use App\Generators\SecurityCodeGenerator;

class SecurityCodeFactory
{
    private DateTimeUTC $dateTimeUTC;

    private SecurityCodeGenerator $securityCodeGenerator;

    public function __construct(DateTimeUTC $dateTimeUTC, SecurityCodeGenerator $securityCodeGenerator)
    {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->securityCodeGenerator = $securityCodeGenerator;
    }

    public function create(): SecurityCode
    {
        return new SecurityCode(
            $this->securityCodeGenerator->generateSecurityCode(),
            $this->dateTimeUTC->createDateTimeInstance()
        );
    }
}
