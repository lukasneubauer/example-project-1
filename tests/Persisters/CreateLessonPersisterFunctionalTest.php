<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Course;
use App\Entities\Lesson;
use App\Http\ApiHeaders;
use App\Persisters\CreateLessonPersister;
use App\Repositories\CourseRepository;
use App\Repositories\LessonRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Tests\EntityManagerCleanup;
use Throwable;

final class CreateLessonPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateLesson(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

            $courseId = '6fd21fb4-5787-4113-9e48-44ded2492608';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);
            $course = $courseRepository->getById($courseId);
            $this->assertCount(1, $course->getLessons());

            /** @var CreateLessonPersister $createLessonPersister */
            $createLessonPersister = $dic->get(CreateLessonPersister::class);
            $newLesson = $createLessonPersister->createLesson(
                [
                    'name' => 'Minulý, přítomný a budoucí čas',
                    'from' => '2000-01-01 14:00:00',
                    'to' => '2000-01-01 16:00:00',
                    'courseId' => $courseId,
                ]
            );

            EntityManagerCleanup::cleanupEntityManager($dic);

            /** @var LessonRepository $lessonRepository */
            $lessonRepository = $dic->get(LessonRepository::class);
            $lesson = $lessonRepository->getById($newLesson->getId());
            $this->assertInstanceOf(Lesson::class, $lesson);
            $this->assertSame('Minulý, přítomný a budoucí čas', $lesson->getName());
            $this->assertSame('2000-01-01 13:00:00', $lesson->getFrom()->format('Y-m-d H:i:s'));
            $this->assertSame('2000-01-01 15:00:00', $lesson->getTo()->format('Y-m-d H:i:s'));
            $this->assertInstanceOf(Course::class, $lesson->getCourse());
            $this->assertSame($courseId, $lesson->getCourse()->getId());
            $this->assertInstanceOf(DateTime::class, $lesson->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson->getUpdatedAt());
            $this->assertSame($lesson->getCreatedAt()->getTimestamp(), $lesson->getUpdatedAt()->getTimestamp());
            $this->assertCount(2, $lesson->getCourse()->getLessons());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
