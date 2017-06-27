<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Course;
use App\Entities\Payment;
use App\Entities\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class PaymentRepository
{
    private EntityRepository $entityRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityRepository = $em->getRepository(Payment::class);
    }

    public function getByCourseAndStudent(Course $course, User $student): ?Payment
    {
        return $this->entityRepository->findOneBy(
            [
                'course' => $course,
                'student' => $student,
            ]
        );
    }
}
