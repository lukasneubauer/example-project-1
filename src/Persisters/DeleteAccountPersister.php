<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Entities\Course;
use App\Entities\Lesson;
use App\Entities\Session;
use App\Entities\User;
use App\Repositories\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class DeleteAccountPersister
{
    private EntityManager $em;

    private UserRepository $userRepository;

    public function __construct(
        EntityManager $em,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteAccount(array $requestData): User
    {
        $user = $this->userRepository->getById($requestData['id']);

        $setUserEmpty = false;

        /** @var Course $course */
        foreach ($user->getTeacherCourses() as $course) {
            $courseHasStudents = \count($course->getStudents()) > 0;
            $courseIsInThePast = false;
            $lessons = $course->getLessons();
            if (\count($lessons) > 0) {
                /** @var Lesson $lastLesson */
                $lastLesson = $lessons->last();
                $courseEndTs = $lastLesson->getTo()->getTimestamp();
                $courseIsInThePast = $courseEndTs < \time();
            }
            if ($courseHasStudents && $courseIsInThePast) {
                $setUserEmpty = true;
            } else {
                /** @var Lesson $lesson */
                foreach ($course->getLessons() as $lesson) {
                    $this->em->remove($lesson);
                }
                $this->em->remove($course);
            }
        }

        /** @var Course $course */
        foreach ($user->getStudentCourses() as $course) {
            $lessons = $course->getLessons();
            /** @var Lesson $lastLesson */
            $lastLesson = $lessons->last();
            $courseEndTs = $lastLesson->getTo()->getTimestamp();
            $courseIsInThePast = $courseEndTs < \time();
            if ($courseIsInThePast) {
                $setUserEmpty = true;
            }
        }

        /** @var Session $session */
        foreach ($user->getSessions() as $session) {
            $this->em->remove($session);
        }

        if ($setUserEmpty) {
            $user->setEmpty();
            $this->em->persist($user);
        } else {
            $this->em->remove($user);
        }

        $this->em->flush();

        return $user;
    }
}
