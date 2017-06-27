<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Http\ApiHeaders;
use App\Persisters\DeleteCourseSubscriptionPersister;
use App\Repositories\CourseRepository;
use App\Repositories\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class DeleteCourseSubscriptionPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testDeleteCourseSubscription(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiToken = 'jkgc66bbpz1a82fjyxsetm7ztgxd5jbq4l7s5rmsotogayonbjxr7ubqsp5ar93ch6oeji1it03k3494';
            $courseId = 'd633abba-9a09-4642-bc27-2ac6698f3463';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var DeleteCourseSubscriptionPersister $deleteCourseSubscriptionPersister */
            $deleteCourseSubscriptionPersister = $dic->get(DeleteCourseSubscriptionPersister::class);
            $deleteCourseSubscriptionPersister->deleteCourseSubscription(['courseId' => $courseId]);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($apiToken);

            /** @var CourseRepository $courseRepository */
            $courseRepository = $dic->get(CourseRepository::class);
            $course = $courseRepository->getById($courseId);

            $courseStudents = $course->getStudents();
            $subscribedStudent = $session->getUser();
            $this->assertFalse($courseStudents->contains($subscribedStudent));
            $this->assertCount(0, $courseStudents);
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
