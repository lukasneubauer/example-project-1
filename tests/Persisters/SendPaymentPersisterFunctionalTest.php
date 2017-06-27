<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Course;
use App\Entities\Payment;
use App\Entities\User;
use App\Http\ApiHeaders;
use App\Persisters\SendPaymentPersister;
use App\Repositories\CourseRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SessionRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class SendPaymentPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testSendPayment(): void
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

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);
            $session = $sessionRepository->getByApiToken($apiToken);
            $student = $session->getUser();

            /** @var PaymentRepository $paymentRepository */
            $paymentRepository = $dic->get(PaymentRepository::class);

            $payment = $paymentRepository->getByCourseAndStudent($course, $student);
            $this->assertNull($payment);

            $requestData = [
                'courseId' => $courseId,
            ];

            /** @var SendPaymentPersister $sendPaymentPersister */
            $sendPaymentPersister = $dic->get(SendPaymentPersister::class);
            $sendPaymentPersister->sendPayment($requestData);

            $newPayment = $paymentRepository->getByCourseAndStudent($course, $student);
            $this->assertInstanceOf(Payment::class, $newPayment);
            $this->assertInstanceOf(Course::class, $newPayment->getCourse());
            $this->assertSame($courseId, $newPayment->getCourse()->getId());
            $this->assertInstanceOf(User::class, $newPayment->getStudent());
            $this->assertSame($student->getId(), $newPayment->getStudent()->getId());
            $this->assertSame(25000, $newPayment->getPrice());
            $this->assertInstanceOf(DateTime::class, $newPayment->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $newPayment->getUpdatedAt());
            $this->assertSame($newPayment->getCreatedAt()->getTimestamp(), $newPayment->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
