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

namespace eTraxis\CommandBus\CommandHandler\Users;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\CommandBus\Command\Users\DisableUsersCommand;
use eTraxis\Repository\UserRepository;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DisableUsersHandler
{
    protected $security;
    protected $repository;
    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepository                $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(AuthorizationCheckerInterface $security, UserRepository $repository, EntityManagerInterface $manager)
    {
        $this->security   = $security;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param DisableUsersCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(DisableUsersCommand $command): void
    {
        $ids = array_unique($command->users);

        /** @var \eTraxis\Entity\User[] $accounts */
        $accounts = $this->repository->findBy([
            'id' => $ids,
        ]);

        if (count($accounts) !== count($ids)) {
            throw new NotFoundHttpException();
        }

        foreach ($accounts as $account) {
            if (!$this->security->isGranted(UserVoter::DISABLE_USER, $account)) {
                throw new AccessDeniedHttpException();
            }
        }

        $query = $this->manager->createQuery('
            UPDATE eTraxis:User u
            SET u.isEnabled = :state
            WHERE u.id IN (:ids)
        ');

        $query->execute([
            'ids'   => $ids,
            'state' => 0,
        ]);
    }
}
