<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\EventBus\Event;

use eTraxis\Entity\User;
use eTraxis\EventBus\EventSubscriber\LockAccount;
use eTraxis\TransactionalTestCase;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \eTraxis\EventBus\EventSubscriber\LockAccount
 */
class LockAccountTest extends TransactionalTestCase
{
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->logger     = new NullLogger();
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testSubscribedEvents()
    {
        $events = LockAccount::getSubscribedEvents();
        self::assertArrayHasKey(LoginFailedEvent::class, $events);
    }

    /**
     * @covers ::handle
     */
    public function testLockUser()
    {
        $event = new LoginFailedEvent([
            'username' => 'artem@example.com',
        ]);

        $handler = new LockAccount($this->logger, $this->repository, 2, 10);

        // first time
        /** @noinspection PhpUnhandledExceptionInspection */
        $handler->handle($event);

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertTrue($user->isAccountNonLocked());

        // second time
        /** @noinspection PhpUnhandledExceptionInspection */
        $handler->handle($event);

        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertFalse($user->isAccountNonLocked());
    }

    /**
     * @covers ::handle
     */
    public function testLockUserForever()
    {
        $event = new LoginFailedEvent([
            'username' => 'artem@example.com',
        ]);

        $handler = new LockAccount($this->logger, $this->repository, 2, null);

        // first time
        /** @noinspection PhpUnhandledExceptionInspection */
        $handler->handle($event);

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertTrue($user->isAccountNonLocked());

        // second time
        /** @noinspection PhpUnhandledExceptionInspection */
        $handler->handle($event);

        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertFalse($user->isAccountNonLocked());
    }

    /**
     * @covers ::handle
     */
    public function testNoLock()
    {
        $event = new LoginFailedEvent([
            'username' => 'artem@example.com',
        ]);

        $handler = new LockAccount($this->logger, $this->repository, null, null);

        // first time
        /** @noinspection PhpUnhandledExceptionInspection */
        $handler->handle($event);

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertTrue($user->isAccountNonLocked());

        // second time
        /** @noinspection PhpUnhandledExceptionInspection */
        $handler->handle($event);

        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertTrue($user->isAccountNonLocked());
    }
}
