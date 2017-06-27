<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Lesson;
use App\Persisters\DeleteLessonPersister;
use App\Repositories\LessonRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Throwable;

final class DeleteLessonPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testDeleteLesson(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            /** @var LessonRepository $lessonRepository */
            $lessonRepository = $dic->get(LessonRepository::class);
            $lessonId = '1b2a40de-6772-4b99-9abf-238cd03054c6';
            $lesson = $lessonRepository->getById($lessonId);
            $this->assertInstanceOf(Lesson::class, $lesson);
            $this->assertSame($lessonId, $lesson->getId());

            /** @var DeleteLessonPersister $deleteLessonPersister */
            $deleteLessonPersister = $dic->get(DeleteLessonPersister::class);
            $deleteLessonPersister->deleteLesson(['id' => $lessonId]);

            $lessonToCheck = $lessonRepository->getById($lessonId);
            $this->assertNull($lessonToCheck);
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
