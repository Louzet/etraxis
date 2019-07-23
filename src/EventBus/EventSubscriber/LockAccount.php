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

use eTraxis\EventBus\Event\LoginFailedEvent;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class LockAccount implements EventSubscriberInterface
{
    protected $logger;
    protected $repository;
    protected $authFailures;
    protected $lockDuration;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface         $logger
     * @param UserRepositoryInterface $repository
     * @param null|int                $authFailures
     * @param null|int                $lockDuration
     */
    public function __construct(
        LoggerInterface         $logger,
        UserRepositoryInterface $repository,
        ?int                    $authFailures,
        ?int                    $lockDuration
    )
    {
        $this->logger       = $logger;
        $this->repository   = $repository;
        $this->authFailures = $authFailures;
        $this->lockDuration = $lockDuration;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LoginFailedEvent::class => 'handle',
        ];
    }

    /**
     * Increases locks count for specified account.
     *
     * @param LoginFailedEvent $event
     *
     * @throws \Exception
     */
    public function handle(LoginFailedEvent $event): void
    {
        if ($this->authFailures === null) {
            return;
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $this->repository->findOneByUsername($event->username);

        if ($user !== null) {

            $this->logger->info('Authentication failure', [$event->username]);

            if ($user->incAuthFailures() >= $this->authFailures) {

                if ($this->lockDuration === null) {
                    $user->lockAccount();
                }
                else {
                    $interval = sprintf('PT%dM', $this->lockDuration);
                    $user->lockAccount(date_create()->add(new \DateInterval($interval)));
                }
            }

            $this->repository->persist($user);
        }
    }
}
