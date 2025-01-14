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

namespace eTraxis\EventBus\EventSubscriber;

use eTraxis\EventBus\Event\LoginSuccessfulEvent;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class UnlockAccount implements EventSubscriberInterface
{
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param UserRepositoryInterface $repository
     */
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LoginSuccessfulEvent::class => 'handle',
        ];
    }

    /**
     * Clears locks count for specified account.
     *
     * @param LoginSuccessfulEvent $event
     */
    public function handle(LoginSuccessfulEvent $event): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->repository->findOneByUsername($event->username);

        if ($user !== null) {

            $user->unlockAccount();

            $this->repository->persist($user);
        }
    }
}
