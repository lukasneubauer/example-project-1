<?php

declare(strict_types=1);

namespace App\EntityFactories;

use App\DateTime\DateTimeUTC;
use App\Entities\Course;
use App\Entities\Payment;
use App\Entities\User;
use App\Generators\UuidGenerator;

class PaymentFactory
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
        User $student,
        int $price
    ): Payment {
        $now = $this->dateTimeUTC->createDateTimeInstance();

        return new Payment(
            $this->uuidGenerator->generateUuid(),
            $course,
            $student,
            $price,
            $now,
            $now
        );
    }
}
