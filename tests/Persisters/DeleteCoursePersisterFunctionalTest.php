<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Course;
use App\Entities\Lesson;
use App\Persisters\DeleteCoursePersister;
use App\Repositories\CourseRepository;
use App\Repositories\LessonRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Throwable;

final class DeleteCoursePersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testDeleteCourse(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);
            $courseId = 'd633abba-9a09-4642-bc27-2ac6698f3463';
            $course = $courseRepository->getById($courseId);
            $this->assertInstanceOf(Course::class, $course);
            $this->assertSame($courseId, $course->getId());

            /** @var LessonRepository $lessonRepository */
            $lessonRepository = $dic->get(LessonRepository::class);
            $lessonId = '1b2a40de-6772-4b99-9abf-238cd03054c6';
            $lesson = $lessonRepository->getById($lessonId);
            $this->assertInstanceOf(Lesson::class, $lesson);
            $this->assertSame($lessonId, $lesson->getId());

            /** @var DeleteCoursePersister $deleteCoursePersister */
            $deleteCoursePersister = $dic->get(DeleteCoursePersister::class);
            $deleteCoursePersister->deleteCourse(['id' => $courseId]);

            $courseToCheck = $courseRepository->getById($courseId);
            $this->assertNull($courseToCheck);

            $lessonToCheck = $lessonRepository->getById($lessonId);
            $this->assertNull($lessonToCheck);
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
