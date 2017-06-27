<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Entities\Course;
use App\Exceptions\CouldNotGetCurrentRequestFromRequestStackException;
use App\Exceptions\NoApiTokenFoundException;
use App\Http\ApiToken;
use App\Repositories\CourseRepository;
use App\Repositories\SessionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class CreateCourseSubscriptionPersister
{
    private ApiToken $apiToken;

    private CourseRepository $courseRepository;

    private EntityManager $em;

    private SessionRepository $sessionRepository;

    public function __construct(
        ApiToken $apiToken,
        CourseRepository $courseRepository,
        EntityManager $em,
        SessionRepository $sessionRepository
    ) {
        $this->apiToken = $apiToken;
        $this->courseRepository = $courseRepository;
        $this->em = $em;
        $this->sessionRepository = $sessionRepository;
    }

    /**
     * @throws CouldNotGetCurrentRequestFromRequestStackException
     * @throws NoApiTokenFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCourseSubscription(array $requestData): Course
    {
        $course = $this->courseRepository->getById($requestData['courseId']);
        $apiToken = $this->apiToken->getApiToken();
        $session = $this->sessionRepository->getByApiToken($apiToken);
        $user = $session->getUser();
        $course->addStudent($user);
        $this->em->persist($course);
        $isStudent = $user->isStudent();
        if ($isStudent === false) {
            $user->setIsStudent(true);
            $this->em->persist($user);
        }
        $this->em->flush();

        return $course;
    }
}
