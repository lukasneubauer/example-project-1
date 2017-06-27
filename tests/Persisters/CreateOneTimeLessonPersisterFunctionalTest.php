<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Course;
use App\Entities\Lesson;
use App\Entities\Subject;
use App\Entities\User;
use App\Http\ApiHeaders;
use App\Persisters\CreateOneTimeLessonPersister;
use App\Repositories\LessonRepository;
use App\Repositories\SessionRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class CreateOneTimeLessonPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateOneTimeLesson(): void
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

            /** @var CreateOneTimeLessonPersister $createOneTimeLessonPersister */
            $createOneTimeLessonPersister = $dic->get(CreateOneTimeLessonPersister::class);

            /** @var LessonRepository $lessonRepository */
            $lessonRepository = $dic->get(LessonRepository::class);

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
                'name' => 'Minulý, přítomný a budoucí čas',
                'subject' => 'Anglický jazyk',
                'price' => 25000,
                'from' => '2000-01-01 14:00:00',
                'to' => '2000-01-01 16:00:00',
            ];

            $newLesson1 = $createOneTimeLessonPersister->createOneTimeLesson($requestData1);
            $lesson1 = $lessonRepository->getById($newLesson1->getId());
            $this->assertInstanceOf(Lesson::class, $lesson1);
            $this->assertSame('Minulý, přítomný a budoucí čas', $lesson1->getName());
            $this->assertSame('2000-01-01 13:00:00', $lesson1->getFrom()->format('Y-m-d H:i:s'));
            $this->assertSame('2000-01-01 15:00:00', $lesson1->getTo()->format('Y-m-d H:i:s'));
            $this->assertInstanceOf(DateTime::class, $lesson1->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson1->getUpdatedAt());
            $this->assertSame($lesson1->getCreatedAt()->getTimestamp(), $lesson1->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Course::class, $lesson1->getCourse());
            $this->assertNull($lesson1->getCourse()->getName());
            $this->assertSame(25000, $lesson1->getCourse()->getPrice());
            $this->assertFalse($lesson1->getCourse()->isActive());
            $this->assertInstanceOf(User::class, $lesson1->getCourse()->getTeacher());
            $this->assertSame($lesson1->getCourse()->getTeacher()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $lesson1->getCourse()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson1->getCourse()->getUpdatedAt());
            $this->assertSame($lesson1->getCourse()->getCreatedAt()->getTimestamp(), $lesson1->getCourse()->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Subject::class, $lesson1->getCourse()->getSubject());
            $this->assertSame('Anglický jazyk', $lesson1->getCourse()->getSubject()->getName());
            $this->assertNull($lesson1->getCourse()->getSubject()->getCreatedBy());
            $this->assertInstanceOf(DateTime::class, $lesson1->getCourse()->getSubject()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson1->getCourse()->getSubject()->getUpdatedAt());
            $this->assertSame($lesson1->getCourse()->getSubject()->getCreatedAt()->getTimestamp(), $lesson1->getCourse()->getSubject()->getUpdatedAt()->getTimestamp());
            $this->assertCount(1, $subjectRepository->getAll());
            $updatedUser = $userRepository->getById($session->getUser()->getId());
            $this->assertTrue($updatedUser->isTeacher());

            $requestData2 = [
                'name' => 'Minulý, přítomný a budoucí čas',
                'subject' => 'Testing Subject',
                'price' => 25000,
                'from' => '2000-01-01 14:00:00',
                'to' => '2000-01-01 16:00:00',
            ];

            $newLesson2 = $createOneTimeLessonPersister->createOneTimeLesson($requestData2);
            $lesson2 = $lessonRepository->getById($newLesson2->getId());
            $this->assertInstanceOf(Lesson::class, $lesson2);
            $this->assertSame('Minulý, přítomný a budoucí čas', $lesson2->getName());
            $this->assertSame('2000-01-01 13:00:00', $lesson2->getFrom()->format('Y-m-d H:i:s'));
            $this->assertSame('2000-01-01 15:00:00', $lesson2->getTo()->format('Y-m-d H:i:s'));
            $this->assertInstanceOf(DateTime::class, $lesson2->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson2->getUpdatedAt());
            $this->assertSame($lesson2->getCreatedAt()->getTimestamp(), $lesson2->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Course::class, $lesson2->getCourse());
            $this->assertNull($lesson2->getCourse()->getName());
            $this->assertSame(25000, $lesson2->getCourse()->getPrice());
            $this->assertFalse($lesson2->getCourse()->isActive());
            $this->assertInstanceOf(User::class, $lesson2->getCourse()->getTeacher());
            $this->assertSame($lesson2->getCourse()->getTeacher()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $lesson2->getCourse()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson2->getCourse()->getUpdatedAt());
            $this->assertSame($lesson2->getCourse()->getCreatedAt()->getTimestamp(), $lesson2->getCourse()->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Subject::class, $lesson2->getCourse()->getSubject());
            $this->assertSame('Testing Subject', $lesson2->getCourse()->getSubject()->getName());
            $this->assertSame($lesson2->getCourse()->getSubject()->getCreatedBy()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $lesson2->getCourse()->getSubject()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson2->getCourse()->getSubject()->getUpdatedAt());
            $this->assertSame($lesson2->getCourse()->getSubject()->getCreatedAt()->getTimestamp(), $lesson2->getCourse()->getSubject()->getUpdatedAt()->getTimestamp());
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
    public function testCreateOneTimeLessonUserBecomesTeacher(): void
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

            /** @var CreateOneTimeLessonPersister $createOneTimeLessonPersister */
            $createOneTimeLessonPersister = $dic->get(CreateOneTimeLessonPersister::class);

            /** @var LessonRepository $lessonRepository */
            $lessonRepository = $dic->get(LessonRepository::class);

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
                'name' => 'Minulý, přítomný a budoucí čas',
                'subject' => 'Anglický jazyk',
                'price' => 25000,
                'from' => '2000-01-01 14:00:00',
                'to' => '2000-01-01 16:00:00',
            ];

            $newLesson = $createOneTimeLessonPersister->createOneTimeLesson($requestData);
            $lesson = $lessonRepository->getById($newLesson->getId());
            $this->assertInstanceOf(Lesson::class, $lesson);
            $this->assertSame('Minulý, přítomný a budoucí čas', $lesson->getName());
            $this->assertSame('2000-01-01 13:00:00', $lesson->getFrom()->format('Y-m-d H:i:s'));
            $this->assertSame('2000-01-01 15:00:00', $lesson->getTo()->format('Y-m-d H:i:s'));
            $this->assertInstanceOf(DateTime::class, $lesson->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson->getUpdatedAt());
            $this->assertSame($lesson->getCreatedAt()->getTimestamp(), $lesson->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Course::class, $lesson->getCourse());
            $this->assertNull($lesson->getCourse()->getName());
            $this->assertSame(25000, $lesson->getCourse()->getPrice());
            $this->assertFalse($lesson->getCourse()->isActive());
            $this->assertInstanceOf(User::class, $lesson->getCourse()->getTeacher());
            $this->assertSame($lesson->getCourse()->getTeacher()->getId(), $user->getId());
            $this->assertInstanceOf(DateTime::class, $lesson->getCourse()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson->getCourse()->getUpdatedAt());
            $this->assertSame($lesson->getCourse()->getCreatedAt()->getTimestamp(), $lesson->getCourse()->getUpdatedAt()->getTimestamp());
            $this->assertInstanceOf(Subject::class, $lesson->getCourse()->getSubject());
            $this->assertSame('Anglický jazyk', $lesson->getCourse()->getSubject()->getName());
            $this->assertNull($lesson->getCourse()->getSubject()->getCreatedBy());
            $this->assertInstanceOf(DateTime::class, $lesson->getCourse()->getSubject()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson->getCourse()->getSubject()->getUpdatedAt());
            $this->assertSame($lesson->getCourse()->getSubject()->getCreatedAt()->getTimestamp(), $lesson->getCourse()->getSubject()->getUpdatedAt()->getTimestamp());
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
