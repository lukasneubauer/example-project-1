<?php

declare(strict_types=1);

namespace App\Persisters;

use App\Entities\Payment;
use App\EntityFactories\PaymentFactory;
use App\Exceptions\CouldNotGetCurrentRequestFromRequestStackException;
use App\Exceptions\NoApiTokenFoundException;
use App\Http\ApiToken;
use App\Repositories\CourseRepository;
use App\Repositories\SessionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

class SendPaymentPersister
{
    private ApiToken $apiToken;

    private CourseRepository $courseRepository;

    private EntityManager $em;

    private PaymentFactory $paymentFactory;

    private SessionRepository $sessionRepository;

    public function __construct(
        ApiToken $apiToken,
        CourseRepository $courseRepository,
        EntityManager $em,
        PaymentFactory $paymentFactory,
        SessionRepository $sessionRepository
    ) {
        $this->apiToken = $apiToken;
        $this->courseRepository = $courseRepository;
        $this->em = $em;
        $this->paymentFactory = $paymentFactory;
        $this->sessionRepository = $sessionRepository;
    }

    /**
     * @throws CouldNotGetCurrentRequestFromRequestStackException
     * @throws NoApiTokenFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function sendPayment(array $requestData): Payment
    {
        $apiToken = $this->apiToken->getApiToken();
        $session = $this->sessionRepository->getByApiToken($apiToken);
        $student = $session->getUser();
        $course = $this->courseRepository->getById($requestData['courseId']);
        $payment = $this->paymentFactory->create($course, $student, $course->getPrice());
        $this->em->persist($payment);
        $this->em->flush();

        return $payment;
    }
}
