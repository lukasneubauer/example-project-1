<?php

declare(strict_types=1);

namespace App\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="`payments`")
 * @ORM\Entity
 */
class Payment
{
    use Id;
    use Timestamp;

    /** @ORM\ManyToOne(targetEntity="Course") */
    private Course $course;

    /** @ORM\ManyToOne(targetEntity="User", inversedBy="payments") */
    private User $student;

    /** @ORM\Column(name="`price`", type="integer", options={"unsigned": true}) */
    private int $price;

    public function __construct(
        string $id,
        Course $course,
        User $student,
        int $price,
        DateTime $createdAt,
        DateTime $updatedAt
    ) {
        $this->id = $id;
        $this->course = $course;
        $this->student = $student;
        $this->price = $price;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function getStudent(): User
    {
        return $this->student;
    }

    public function getPrice(): int
    {
        return $this->price;
    }
}
