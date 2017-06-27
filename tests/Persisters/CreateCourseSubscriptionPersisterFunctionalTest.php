<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Http\ApiHeaders;
use App\Persisters\CreateCourseSubscriptionPersister;
use App\Repositories\CourseRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class CreateCourseSubscriptionPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testCreateCourseSubscription(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'ellbb4woofjai50hut2d2sa1q0yd9rsdq7bdtypvtnuesj64vqhm6rpq8bzfym3sxfa205rx0xrppg34';
            $courseId = 'd633abba-9a09-4642-bc27-2ac6698f3463';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($apiToken);
            $userToSubscribe = $session->getUser();
            $this->assertTrue($userToSubscribe->isStudent());

            /** @var CreateCourseSubscriptionPersister $createCourseSubscriptionPersister */
            $createCourseSubscriptionPersister = $dic->get(CreateCourseSubscriptionPersister::class);
            $createCourseSubscriptionPersister->createCourseSubscription(['courseId' => $courseId]);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);
            $course = $courseRepository->getById($courseId);

            $courseStudents = $course->getStudents();
            $subscribedStudent = $session->getUser();
            $this->assertTrue($courseStudents->contains($subscribedStudent));
            $this->assertCount(2, $courseStudents);

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);
            $updatedUser = $userRepository->getById($session->getUser()->getId());
            $this->assertTrue($updatedUser->isStudent());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateCourseSubscriptionUserBecomesStudent(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'wdcixjplrfmay1cvi78rwtyuhljn3whpuv5p4v595h9k12x15nwd2fczirmgxb4su70n8kl3ilxberyl';
            $courseId = 'd633abba-9a09-4642-bc27-2ac6698f3463';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($apiToken);
            $userToSubscribe = $session->getUser();
            $this->assertFalse($userToSubscribe->isStudent());

            /** @var CreateCourseSubscriptionPersister $createCourseSubscriptionPersister */
            $createCourseSubscriptionPersister = $dic->get(CreateCourseSubscriptionPersister::class);
            $createCourseSubscriptionPersister->createCourseSubscription(['courseId' => $courseId]);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);
            $course = $courseRepository->getById($courseId);

            $courseStudents = $course->getStudents();
            $subscribedStudent = $session->getUser();
            $this->assertTrue($courseStudents->contains($subscribedStudent));
            $this->assertCount(2, $courseStudents);

            /** @var UserRepository $userRepository */
            $userRepository = $dic->get(UserRepository::class);
            $updatedUser = $userRepository->getById($session->getUser()->getId());
            $this->assertTrue($updatedUser->isStudent());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
