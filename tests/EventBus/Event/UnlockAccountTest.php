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
use eTraxis\EventBus\EventSubscriber\UnlockAccount;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\EventBus\EventSubscriber\UnlockAccount
 */
class UnlockAccountTest extends TransactionalTestCase
{
    /**
     * @covers ::getSubscribedEvents
     */
    public function testSubscribedEvents()
    {
        $events = UnlockAccount::getSubscribedEvents();
        self::assertArrayHasKey(LoginSuccessfulEvent::class, $events);
    }

    /**
     * @covers ::handle
     */
    public function testUnlockUser()
    {
        /** @var \eTraxis\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');
        $user->lockAccount();

        self::assertFalse($user->isAccountNonLocked());

        $event = new LoginSuccessfulEvent([
            'username' => $user->getUsername(),
        ]);

        $this->eventBus->notify($event);

        self::assertTrue($user->isAccountNonLocked());
    }
}
