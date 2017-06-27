<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Course;
use App\Entities\Subject;
use App\Entities\User;
use App\Http\ApiHeaders;
use App\Persisters\CreateCoursePersister;
use App\Repositories\CourseRepository;
use App\Repositories\SessionRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class CreateCoursePersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateCourse(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var CreateCoursePersister $createCoursePersister */
            $createCoursePersister = $dic->get(CreateCoursePersister::class);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($apiToken);

            /** @var SubjectRepository $subjectRepository */
            $subjectRepository = $dic->get(SubjectRepository::class);
            $this->assertCount(1, $subjectRepository->getAll());

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);
            $user = $userRepository->getById($session->getUser()->getId());
            $this->assertTrue($user->isTeacher());

            $requestData1 = [
                'name' => 'Letní doučování angličtiny',
                'subject' => 'Anglický jazyk',
                'price' => 25000,
            ];

            $newCourse1 = $createCoursePersister->createCourse($requestData1);
            $course1 = $courseRepository->getById($newCourse1->getId());
            $this->assertInstanceOf(Course::class, $course1);
            $this->assertSame('Letní doučování angličtiny', $course1->getName());
            $this->assertSame(25000, $course1->getPrice());
            $this->assertFalse($course1->isActive());
            $this->assertInstanceOf(User::class, $course1->getTeacher());
            $this->assertSame($course1->getTeacher()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $course1->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course1->getUpdatedAt());
            $this->assertSame($course1->getCreatedAt()->getTimestamp(), $course1->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Subject::class, $course1->getSubject());
            $this->assertSame('Anglický jazyk', $course1->getSubject()->getName());
            $this->assertNull($course1->getSubject()->getCreatedBy());
            $this->assertInstanceOf(DateTime::class, $course1->getSubject()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course1->getSubject()->getUpdatedAt());
            $this->assertSame($course1->getSubject()->getCreatedAt()->getTimestamp(), $course1->getSubject()->getUpdatedAt()->getTimestamp());
            $this->assertCount(1, $subjectRepository->getAll());
            $updatedUser = $userRepository->getById($session->getUser()->getId());
            $this->assertTrue($updatedUser->isTeacher());

            $requestData2 = [
                'name' => 'Letní doučování angličtiny',
                'subject' => 'Testing Subject',
                'price' => 25000,
            ];

            $newCourse2 = $createCoursePersister->createCourse($requestData2);
            $course2 = $courseRepository->getById($newCourse2->getId());
            $this->assertInstanceOf(Course::class, $course2);
            $this->assertSame('Letní doučování angličtiny', $course2->getName());
            $this->assertSame(25000, $course2->getPrice());
            $this->assertFalse($course2->isActive());
            $this->assertInstanceOf(User::class, $course2->getTeacher());
            $this->assertSame($course2->getTeacher()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $course2->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course2->getUpdatedAt());
            $this->assertSame($course2->getCreatedAt()->getTimestamp(), $course2->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Subject::class, $course2->getSubject());
            $this->assertSame('Testing Subject', $course2->getSubject()->getName());
            $this->assertSame($course2->getSubject()->getCreatedBy()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $course2->getSubject()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course2->getSubject()->getUpdatedAt());
            $this->assertSame($course2->getSubject()->getCreatedAt()->getTimestamp(), $course2->getSubject()->getUpdatedAt()->getTimestamp());
            $this->assertCount(2, $subjectRepository->getAll());
            $updatedUser = $userRepository->getById($session->getUser()->getId());
            $this->assertTrue($updatedUser->isTeacher());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateCourseUserBecomesTeacher(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'jkgc66bbpz1a82fjyxsetm7ztgxd5jbq4l7s5rmsotogayonbjxr7ubqsp5ar93ch6oeji1it03k3494';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var CreateCoursePersister $createCoursePersister */
            $createCoursePersister = $dic->get(CreateCoursePersister::class);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($apiToken);

            /** @var SubjectRepository $subjectRepository */
            $subjectRepository = $dic->get(SubjectRepository::class);
            $this->assertCount(1, $subjectRepository->getAll());

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);
            $user = $userRepository->getById($session->getUser()->getId());
            $this->assertFalse($user->isTeacher());

            $requestData = [
                'name' => 'Letní doučování angličtiny',
                'subject' => 'Anglický jazyk',
                'price' => 25000,
            ];

            $newCourse = $createCoursePersister->createCourse($requestData);
            $course = $courseRepository->getById($newCourse->getId());
            $this->assertInstanceOf(Course::class, $course);
            $this->assertSame('Letní doučování angličtiny', $course->getName());
            $this->assertSame(25000, $course->getPrice());
            $this->assertFalse($course->isActive());
            $this->assertInstanceOf(User::class, $course->getTeacher());
            $this->assertSame($course->getTeacher()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $course->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course->getUpdatedAt());
            $this->assertSame($course->getCreatedAt()->getTimestamp(), $course->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Subject::class, $course->getSubject());
            $this->assertSame('Anglický jazyk', $course->getSubject()->getName());
            $this->assertNull($course->getSubject()->getCreatedBy());
            $this->assertInstanceOf(DateTime::class, $course->getSubject()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $course->getSubject()->getUpdatedAt());
            $this->assertSame($course->getSubject()->getCreatedAt()->getTimestamp(), $course->getSubject()->getUpdatedAt()->getTimestamp());
            $this->assertCount(1, $subjectRepository->getAll());
            $updatedUser = $userRepository->getById($session->getUser()->getId());
            $this->assertTrue($updatedUser->isTeacher());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
