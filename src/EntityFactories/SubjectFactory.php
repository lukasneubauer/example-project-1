<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Subject;
use App\Entities\User;
use App\Generators\UuidGenerator;

class SubjectFactory
{
    private DateTimeUTC $dateTimeUTC;

    private UuidGenerator $uuidGenerator;

    public function __construct(DateTimeUTC $dateTimeUTC, UuidGenerator $uuidGenerator)
    {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function create(User $createdBy, string $name): Subject
    {
        $now = $this->dateTimeUTC->createDateTimeInstance();

        return new Subject(
            $this->uuidGenerator->generateUuid(),
            $createdBy,
            $name,
            $now,
            $now
        );
    }
}
