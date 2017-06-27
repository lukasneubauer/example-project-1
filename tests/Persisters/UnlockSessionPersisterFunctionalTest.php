<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Password;
use App\Http\ApiHeaders;
use App\Passwords\PasswordAlgorithms;
use App\Persisters\UnlockSessionPersister;
use App\Repositories\SessionRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Throwable;

final class UnlockSessionPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testUnlockSession(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        try {
            $apiClientId = 'CLIENT-ID';
            $apiToken = '6ejocghxhtueedkvn1s5dx8cxtb59g21i87x3bjngv88azujmtoy7xsum60lzp4bq24q3fogrijyhalh';

            $request = new Request();
            $request->headers->set(ApiHeaders::API_CLIENT_ID, $apiClientId);
            $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

            /** @var RequestStack $requestStack */
            $requestStack = $dic->get(RequestStack::class);
            $requestStack->push($request);

            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $dic->get(SessionRepository::class);

            $session = $sessionRepository->getByApiToken($apiToken);
            $user = $session->getUser();
            $oldPassword = $user->getPassword();
            $userUpdatedAt = $user->getUpdatedAt();

            $this->assertTrue($session->isLocked());

            /** @var UnlockSessionPersister $unlockSessionPersister */
            $unlockSessionPersister = $dic->get(UnlockSessionPersister::class);
            $unlockedSession = $unlockSessionPersister->unlockSession(['password' => 'secret']);

            $this->assertInstanceOf(Password::class, $unlockedSession->getUser()->getPassword());
            $this->assertTrue(\is_string($unlockedSession->getUser()->getPassword()->getHash()));
            $this->assertSame(60, \strlen($unlockedSession->getUser()->getPassword()->getHash()));
            $this->assertStringStartsWith('$2y$13$', $unlockedSession->getUser()->getPassword()->getHash());
            $this->assertSame(PasswordAlgorithms::BCRYPT, $unlockedSession->getUser()->getPassword()->getAlgorithm());
            $this->assertTrue(\password_verify('secret', $unlockedSession->getUser()->getPassword()->getHash()));
            $this->assertSame($oldPassword->getHash(), $unlockedSession->getUser()->getPassword()->getHash());
            $this->assertSame(0, $unlockedSession->getUser()->getAuthenticationFailures());
            $this->assertInstanceOf(DateTime::class, $unlockedSession->getUser()->getCreatedAt());
            $this->assertInstanceOf(DateTime::class, $unlockedSession->getUser()->getUpdatedAt());
            $this->assertGreaterThan($userUpdatedAt->getTimestamp(), $unlockedSession->getUser()->getUpdatedAt()->getTimestamp());
            $this->assertSame($apiClientId, $unlockedSession->getApiClientId());
            $this->assertFalse($unlockedSession->isLocked());
            $this->assertInstanceOf(DateTime::class, $unlockedSession->getUpdatedAt());
            $this->assertSame($unlockedSession->getUpdatedAt()->getTimestamp(), $unlockedSession->getUser()->getUpdatedAt()->getTimestamp());
        } catch (Throwable $e) {
            throw $e;
        } finally {
            Database::resetDatabase($dic);
        }
    }
}
