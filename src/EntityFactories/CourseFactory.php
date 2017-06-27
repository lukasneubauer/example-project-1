<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Course;
use App\Entities\Subject;
use App\Entities\User;
use App\Generators\UuidGenerator;

class CourseFactory
{
    private DateTimeUTC $dateTimeUTC;

    private UuidGenerator $uuidGenerator;

    public function __construct(DateTimeUTC $dateTimeUTC, UuidGenerator $uuidGenerator)
    {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function create(
        Subject $subject,
        User $teacher,
        ?string $name,
        int $price
    ): Course {
        $now = $this->dateTimeUTC->createDateTimeInstance();

        return new Course(
            $this->uuidGenerator->generateUuid(),
            $subject,
            $teacher,
            $name,
            $price,
            false,
            $now,
            $now
        );
    }
}
