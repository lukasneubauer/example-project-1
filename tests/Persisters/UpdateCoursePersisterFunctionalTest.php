<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Course;
use App\Entities\Subject;
use App\Entities\User;
use App\Http\ApiHeaders;
use App\Persisters\UpdateCoursePersister;
use App\Repositories\CourseRepository;
use App\Repositories\SessionRepository;
use App\Repositories\SubjectRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class UpdateCoursePersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testUpdateCourse(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

            $courseId = 'd633abba-9a09-4642-bc27-2ac6698f3463';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var UpdateCoursePersister $updateCoursePersister */
            $updateCoursePersister = $dic->get(UpdateCoursePersister::class);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($apiToken);
            $user = $session->getUser();

            $courseBeforeUpdate = $courseRepository->getById($courseId);
            $courseUpdatedAt = $courseBeforeUpdate->getUpdatedAt();

            /** @var SubjectRepository $subjectRepository */
            $subjectRepository = $dic->get(SubjectRepository::class);
            $this->assertCount(1, $subjectRepository->getAll());

            $requestData = [
                'name' => 'Letní doučování angličtiny 2',
                'subject' => 'Anglický jazyk 2',
                'price' => 50000,
                'isActive' => false,
            ];

            $updateCoursePersister->updateCourse($requestData, $courseId);
            $course = $courseRepository->getById($courseId);
            $this->assertInstanceOf(Course::class, $course);
            $this->assertSame('Letní doučování angličtiny 2', $course->getName());
            $this->assertSame(50000, $course->getPrice());
            $this->assertFalse($course->isActive());
            $this->assertInstanceOf(User::class, $course->getTeacher());
            $this->assertSame($course->getTeacher()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $course->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course->getUpdatedAt());
            $this->assertGreaterThan($courseUpdatedAt->getTimestamp(), $course->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Subject::class, $course->getSubject());
            $this->assertSame('Anglický jazyk 2', $course->getSubject()->getName());
            $this->assertSame($course->getSubject()->getCreatedBy()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $course->getSubject()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course->getSubject()->getUpdatedAt());
            $this->assertSame($course->getSubject()->getCreatedAt()->getTimestamp(), $course->getSubject()->getUpdatedAt()->getTimestamp());
            $this->assertCount(2, $subjectRepository->getAll());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
