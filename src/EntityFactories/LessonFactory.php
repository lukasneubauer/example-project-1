<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Course;
use App\Entities\Lesson;
use App\Generators\UuidGenerator;
use DateTime;

class LessonFactory
{
    private DateTimeUTC $dateTimeUTC;

    private UuidGenerator $uuidGenerator;

    public function __construct(DateTimeUTC $dateTimeUTC, UuidGenerator $uuidGenerator)
    {
        $this->dateTimeUTC = $dateTimeUTC;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function create(
        Course $course,
        DateTime $from,
        DateTime $to,
        string $name
    ): Lesson {
        $now = $this->dateTimeUTC->createDateTimeInstance();

        return new Lesson(
            $this->uuidGenerator->generateUuid(),
            $course,
            $from,
            $to,
            $name,
            $now,
            $now
        );
    }
}
