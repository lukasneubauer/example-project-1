<?php

declare(strict_types=1);

namespace Tests\App\Persisters;

use App\Entities\Password;
use App\Exceptions\CouldNotPersistException;
use App\Http\ApiHeaders;
use App\Passwords\PasswordAlgorithms;
use App\Passwords\PasswordSettings;
use App\Persisters\SettingsPersister;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Database;
use Tests\PasswordSettingsWithPredefinedValues;
use Throwable;

/**
 * This test is calling tearDown() method instead of using try..catch..finally because of entity manager locking.
 */
final class SettingsPersisterFunctionalTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testUpdateSettings(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

        $request = new Request();
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

        /** @var SettingsPersister $settingsPersister */
        $settingsPersister = $dic->get(SettingsPersister::class);

        $requestData = [
            'firstName' => 'Frank',
            'lastName' => 'Sinatra',
            'email' => 'frank.sinatra@example.com',
            'password' => null,
            'isTeacher' => true,
            'timezone' => 'Europe/Prague',
        ];

        $settingsPersister->updateSettings($requestData);

        /** @var UserRepository $userRepository */
        $userRepository = $dic->get(UserRepository::class);

        $userToCheck = $userRepository->getByEmail('frank.sinatra@example.com');
        $this->assertSame('Frank', $userToCheck->getFirstName());
        $this->assertSame('Sinatra', $userToCheck->getLastName());
        $this->assertSame('frank.sinatra@example.com', $userToCheck->getEmail());
        $this->assertInstanceOf(Password::class, $userToCheck->getPassword());
        $this->assertTrue(\is_string($userToCheck->getPassword()->getHash()));
        $this->assertSame(60, \strlen($userToCheck->getPassword()->getHash()));
        $this->assertStringStartsWith('$2y$13$', $userToCheck->getPassword()->getHash());
        $this->assertSame(PasswordAlgorithms::BCRYPT, $userToCheck->getPassword()->getAlgorithm());
        $this->assertTrue(\password_verify('secret', $userToCheck->getPassword()->getHash()));
        $this->assertSame($oldPassword->getHash(), $userToCheck->getPassword()->getHash());
        $this->assertTrue($userToCheck->isTeacher());
        $this->assertFalse($userToCheck->isStudent());
        $this->assertSame('Europe/Prague', $userToCheck->getTimezone());
        $this->assertInstanceOf(DateTime::class, $userToCheck->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $userToCheck->getUpdatedAt());
        $this->assertGreaterThan($userUpdatedAt->getTimestamp(), $userToCheck->getUpdatedAt()->getTimestamp());
    }

    /**
     * @throws Throwable
     */
    public function testUpdateSettingsAlsoUpdatesPassword(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

        $request = new Request();
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

        /** @var SettingsPersister $settingsPersister */
        $settingsPersister = $dic->get(SettingsPersister::class);

        $requestData = [
            'firstName' => 'Frank',
            'lastName' => 'Sinatra',
            'email' => 'frank.sinatra@example.com',
            'password' => 'secret',
            'isTeacher' => true,
            'timezone' => 'Europe/Prague',
        ];

        $settingsPersister->updateSettings($requestData);

        /** @var UserRepository $userRepository */
        $userRepository = $dic->get(UserRepository::class);

        $userToCheck = $userRepository->getByEmail('frank.sinatra@example.com');
        $this->assertSame('Frank', $userToCheck->getFirstName());
        $this->assertSame('Sinatra', $userToCheck->getLastName());
        $this->assertSame('frank.sinatra@example.com', $userToCheck->getEmail());
        $this->assertInstanceOf(Password::class, $userToCheck->getPassword());
        $this->assertTrue(\is_string($userToCheck->getPassword()->getHash()));
        $this->assertSame(60, \strlen($userToCheck->getPassword()->getHash()));
        $this->assertStringStartsWith('$2y$13$', $userToCheck->getPassword()->getHash());
        $this->assertSame(PasswordAlgorithms::BCRYPT, $userToCheck->getPassword()->getAlgorithm());
        $this->assertTrue(\password_verify('secret', $userToCheck->getPassword()->getHash()));
        $this->assertNotSame($oldPassword->getHash(), $userToCheck->getPassword()->getHash());
        $this->assertTrue($userToCheck->isTeacher());
        $this->assertFalse($userToCheck->isStudent());
        $this->assertSame('Europe/Prague', $userToCheck->getTimezone());
        $this->assertInstanceOf(DateTime::class, $userToCheck->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $userToCheck->getUpdatedAt());
        $this->assertGreaterThan($userUpdatedAt->getTimestamp(), $userToCheck->getUpdatedAt()->getTimestamp());
    }

    /**
     * @throws Throwable
     */
    public function testUpdateSettingsAlsoUpdatesPasswordToNewAlgorithm(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

        $request = new Request();
        $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

        /** @var RequestStack $requestStack */
        $requestStack = $dic->get(RequestStack::class);
        $requestStack->push($request);

        /** @var PasswordSettings $passwordSettings */
        $passwordSettings = $dic->get(PasswordSettingsWithPredefinedValues::class);
        $dic->set(PasswordSettings::class, $passwordSettings);

        /** @var SessionRepository $sessionRepository */
        $sessionRepository = $dic->get(SessionRepository::class);

        $session = $sessionRepository->getByApiToken($apiToken);
        $user = $session->getUser();
        $oldPassword = $user->getPassword();
        $userUpdatedAt = $user->getUpdatedAt();

        /** @var SettingsPersister $settingsPersister */
        $settingsPersister = $dic->get(SettingsPersister::class);

        $requestData = [
            'firstName' => 'Frank',
            'lastName' => 'Sinatra',
            'email' => 'frank.sinatra@example.com',
            'password' => 'secret',
            'isTeacher' => true,
            'timezone' => 'Europe/Prague',
        ];

        $settingsPersister->updateSettings($requestData);

        /** @var UserRepository $userRepository */
        $userRepository = $dic->get(UserRepository::class);

        $userToCheck = $userRepository->getByEmail('frank.sinatra@example.com');
        $this->assertSame('Frank', $userToCheck->getFirstName());
        $this->assertSame('Sinatra', $userToCheck->getLastName());
        $this->assertSame('frank.sinatra@example.com', $userToCheck->getEmail());
        $this->assertInstanceOf(Password::class, $userToCheck->getPassword());
        $this->assertTrue(\is_string($userToCheck->getPassword()->getHash()));
        $this->assertSame(96, \strlen($userToCheck->getPassword()->getHash()));
        $this->assertStringStartsWith('$argon2i$v=19$m=65536,t=4,p=1$', $userToCheck->getPassword()->getHash());
        $this->assertSame(PasswordAlgorithms::ARGON2I, $userToCheck->getPassword()->getAlgorithm());
        $this->assertTrue(\password_verify('secret', $userToCheck->getPassword()->getHash()));
        $this->assertNotSame($oldPassword->getHash(), $userToCheck->getPassword()->getHash());
        $this->assertTrue($userToCheck->isTeacher());
        $this->assertFalse($userToCheck->isStudent());
        $this->assertSame('Europe/Prague', $userToCheck->getTimezone());
        $this->assertInstanceOf(DateTime::class, $userToCheck->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $userToCheck->getUpdatedAt());
        $this->assertGreaterThan($userUpdatedAt->getTimestamp(), $userToCheck->getUpdatedAt()->getTimestamp());
    }

    /**
     * @throws Throwable
     */
    public function testUpdateSettingsThrowsException(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        $apiToken = 'pxlph3gpe4jaaz87zfmgwq4bhiqcj2xofgrxqndtfssvtjap5od2rxuhivm2htpzg548cff9j6wdr2es';

        $request = new Request();
        $request->headers->set(ApiHeaders::API_TOKEN, $apiToken);

        /** @var RequestStack $requestStack */
        $requestStack = $dic->get(RequestStack::class);
        $requestStack->push($request);

        /** @var SettingsPersister $settingsPersister */
        $settingsPersister = $dic->get(SettingsPersister::class);

        try {
            $requestData = [
                'firstName' => 'Jake',
                'lastName' => 'Doe',
                'email' => 'jake.doe@example.com',
                'password' => 'secret',
                'isTeacher' => true,
                'timezone' => 'Europe/Prague',
            ];

            $settingsPersister->updateSettings($requestData);

            $this->fail('Failed to throw exception.');
        } catch (CouldNotPersistException $e) {
            $data = $e->getData();
            $this->assertSame(14, $data['error']['code']);
            $this->assertSame("Value for 'email' in request body is already taken.", $data['error']['message']);
            $this->assertSame("Value for 'email' in request body is already taken.", $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        static::bootKernel();

        $dic = static::getContainer();

        Database::resetDatabase($dic);
    }
}
