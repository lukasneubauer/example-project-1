<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Session;
use App\Persisters\DeleteAccountPersister;
use App\Repositories\CourseRepository;
use App\Repositories\LessonRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Database;
use Throwable;

final class DeleteAccountPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @dataProvider getData
     *
     * @throws Throwable
     */
    public function testDeleteAccount(
        string $id,
        bool $isDeleted,
        ?string $courseIdToCheck = null,
        ?string $lessonIdToCheck = null
    ): void {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);

            $userBeforeDeletion = $userRepository->getById($id);
            $userBeforeDeletionUpdatedAt = $userBeforeDeletion->getUpdatedAt();
            $sessions = $userBeforeDeletion->getSessions();
            $this->assertGreaterThan(0, \count($sessions));

            /** @var DeleteAccountPersister $deleteAccountPersister */
            $deleteAccountPersister = $dic->get(DeleteAccountPersister::class);
            $deleteAccountPersister->deleteAccount(['id' => $id]);

            $user = $userRepository->getById($id);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);

            /** @var LessonRepository $lessonRepository */
            $lessonRepository = $dic->get(LessonRepository::class);

            if ($isDeleted) {
                $this->assertNull($user);

                /** @var SessionRepository $sessionRepository */
                $sessionRepository = $dic->get(SessionRepository::class);

                /** @var Session $session */
                foreach ($sessions as $session) {
                    $sess = $sessionRepository->getByApiToken($session->getCurrentApiToken());
                    $this->assertNull($sess);
                }

                if ($courseIdToCheck !== null) {
                    $course = $courseRepository->getById($courseIdToCheck);
                    $this->assertNull($course);
                }

                if ($lessonIdToCheck !== null) {
                    $lesson = $lessonRepository->getById($lessonIdToCheck);
                    $this->assertNull($lesson);
                }
            } else {
                $this->assertNotNull($user);
                $this->assertTrue($user->isEmpty());
                $this->assertCount(0, $user->getSessions());
                $this->assertGreaterThan(
                    $userBeforeDeletionUpdatedAt->getTimestamp(),
                    $user->getUpdatedAt()->getTimestamp()
                );

                if ($courseIdToCheck !== null) {
                    $course = $courseRepository->getById($courseIdToCheck);
                    $this->assertNotNull($course);
                }

                if ($lessonIdToCheck !== null) {
                    $lesson = $lessonRepository->getById($lessonIdToCheck);
                    $this->assertNotNull($lesson);
                }
            }
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    public function getData(): array
    {
        return [
            [
                '7daef828-ae8f-4be7-b71f-385eca118008', // user who is teacher but has no courses
                true,
                null,
                null,
            ],
            [
                '01c1c6dc-b3ac-494a-bb52-571540c87305', // user who is teacher who has one course but it is not active
                true,
                'f8b4d448-72dd-432f-a2a9-6256ba75636d',
                null,
            ],
            [
                'bed66669-ca72-459a-9f27-4e1132b4dd08', // user who is teacher who has one course which is active but it has no students
                true,
                '7ec8d249-21ca-4468-9bc3-820bcb70418d',
                null,
            ],
            [
                'fb591a7b-83d7-4170-a795-88307f11123e', // user who is teacher who has one course which is active and it has one student but it is in the past
                false,
                '06c7e6ef-2232-4445-abf6-f98d529c7c5f',
                'bdf264e5-1a17-4880-9688-7783e143a498',
            ],
            [
                'ec6506e9-764e-4243-8299-c53b3615cff8', // user who is teacher who has one course which is active and it has one student but it is in the future
                true,
                'd49d4258-44e4-4cce-b5cb-29dc85a16d07',
                '0917142b-047e-4b45-a357-035495dd231f',
            ],
            [
                '3f150ae6-ece6-410a-84ca-754298d1a20d', // user who is student who is not subscribed to any course
                true,
                null,
                null,
            ],
            [
                'adfdcdfe-a77b-4b01-b530-93a0de4e1cd8', // user who is student who is subscribed to one course which is in the past
                false,
                null,
                null,
            ],
            [
                '81ea4300-835c-4453-9197-e80dc0276adc', // user who is student who is subscribed to one course which is in the future
                true,
                null,
                null,
            ],
        ];
    }
}
