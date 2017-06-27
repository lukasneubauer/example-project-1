<?php

declare(strict_types=1);

namespace App\Persisters;

use App\DateTime\GivenTimezoneToUTC;
use App\Entities\Lesson;
use App\EntityFactories\CourseFactory;
use App\EntityFactories\LessonFactory;
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

class CreateOneTimeLessonPersister
{
    private ApiToken $apiToken;

    private CourseFactory $courseFactory;

    private EntityManager $em;

    private GivenTimezoneToUTC $givenTimezoneToUTC;

    private LessonFactory $lessonFactory;

    private SessionRepository $sessionRepository;

    private SubjectFactory $subjectFactory;

    private SubjectRepository $subjectRepository;

    public function __construct(
        ApiToken $apiToken,
        CourseFactory $courseFactory,
        EntityManager $em,
        GivenTimezoneToUTC $givenTimezoneToUTC,
        LessonFactory $lessonFactory,
        SessionRepository $sessionRepository,
        SubjectFactory $subjectFactory,
        SubjectRepository $subjectRepository
    ) {
        $this->apiToken = $apiToken;
        $this->courseFactory = $courseFactory;
        $this->em = $em;
        $this->givenTimezoneToUTC = $givenTimezoneToUTC;
        $this->lessonFactory = $lessonFactory;
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
    public function createOneTimeLesson(array $requestData): Lesson
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
        $timezone = $teacher->getTimezone();
        $course = $this->courseFactory->create($subject, $teacher, null, $requestData['price']);
        $this->em->persist($course);
        $lesson = $this->lessonFactory->create(
            $course,
            $this->givenTimezoneToUTC->convertGivenTimezoneToUTC($timezone, $requestData['from']),
            $this->givenTimezoneToUTC->convertGivenTimezoneToUTC($timezone, $requestData['to']),
            $requestData['name']
        );
        $this->em->persist($lesson);
        $isTeacher = $teacher->isTeacher();
        if ($isTeacher === false) {
            $teacher->setIsTeacher(true);
            $this->em->persist($teacher);
        }
        $this->em->flush();

        return $lesson;
    }
}
