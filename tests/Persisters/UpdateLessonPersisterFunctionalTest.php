<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Course;
use App\Entities\Lesson;
use App\Http\ApiHeaders;
use App\Persisters\UpdateLessonPersister;
use App\Repositories\LessonRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class UpdateLessonPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testUpdateLesson(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

            $lessonId = '1b2a40de-6772-4b99-9abf-238cd03054c6';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var LessonRepository $lessonRepository */
            $lessonRepository = $dic->get(LessonRepository::class);
            $lessonBeforeUpdate = $lessonRepository->getById($lessonId);
            $lessonUpdatedAt = $lessonBeforeUpdate->getUpdatedAt();

            $requestData = [
                'name' => 'Minulý, přítomný a budoucí čas 2',
                'from' => '2000-01-01 10:00:00',
                'to' => '2000-01-01 12:00:00',
                'courseId' => '6fd21fb4-5787-4113-9e48-44ded2492608',
            ];

            /** @var UpdateLessonPersister $updateLessonPersister */
            $updateLessonPersister = $dic->get(UpdateLessonPersister::class);
            $updateLessonPersister->updateLesson($requestData, $lessonId);

            $lesson = $lessonRepository->getById($lessonId);
            $this->assertInstanceOf(Lesson::class, $lesson);
            $this->assertSame('Minulý, přítomný a budoucí čas 2', $lesson->getName());
            $this->assertSame('2000-01-01 09:00:00', $lesson->getFrom()->format('Y-m-d H:i:s'));
            $this->assertSame('2000-01-01 11:00:00', $lesson->getTo()->format('Y-m-d H:i:s'));
            $this->assertInstanceOf(Course::class, $lesson->getCourse());
            $this->assertSame('6fd21fb4-5787-4113-9e48-44ded2492608', $lesson->getCourse()->getId());
            $this->assertInstanceOf(DateTime::class, $lesson->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $lesson->getUpdatedAt());
            $this->assertGreaterThan($lessonUpdatedAt->getTimestamp(), $lesson->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
