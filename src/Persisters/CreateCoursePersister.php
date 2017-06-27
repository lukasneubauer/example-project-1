<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Entities\Course;
use App\EntityFactories\CourseFactory;
use App\EntityFactories\SubjectFactory;
use App\Exceptions\CouldNotGetCurrentRequestFromRequestStackException;
use App\Exceptions\NoApiTokenFoundException;
use App\Http\ApiToken;
use App\Repositories\SessionRepository;
use App\Repositories\SubjectRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class CreateCoursePersister
{
    private ApiToken $apiToken;

    private CourseFactory $courseFactory;

    private EntityManager $em;

    private SessionRepository $sessionRepository;

    private SubjectFactory $subjectFactory;

    private SubjectRepository $subjectRepository;

    public function __construct(
        ApiToken $apiToken,
        CourseFactory $courseFactory,
        EntityManager $em,
        SessionRepository $sessionRepository,
        SubjectFactory $subjectFactory,
        SubjectRepository $subjectRepository
    ) {
        $this->apiToken = $apiToken;
        $this->courseFactory = $courseFactory;
        $this->em = $em;
        $this->sessionRepository = $sessionRepository;
        $this->subjectFactory = $subjectFactory;
        $this->subjectRepository = $subjectRepository;
    }

    /**
     * @throws CouldNotGetCurrentRequestFromRequestStackException
     * @throws NoApiTokenFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCourse(array $requestData): Course
    {
        $apiToken = $this->apiToken->getApiToken();
        $session = $this->sessionRepository->getByApiToken($apiToken);
        $teacher = $session->getUser();
        $subject = $this->subjectRepository->getByName($requestData['subject']);
        if ($subject === null) {
            $subject = $this->subjectFactory->create($teacher, $requestData['subject']);
            try {
                $this->em->persist($subject);
            } catch (UniqueConstraintViolationException $e) {
            }
        }
        $course = $this->courseFactory->create($subject, $teacher, $requestData['name'], $requestData['price']);
        $this->em->persist($course);
        $isTeacher = $teacher->isTeacher();
        if ($isTeacher === false) {
            $teacher->setIsTeacher(true);
            $this->em->persist($teacher);
        }
        $this->em->flush();

        return $course;
    }
}
