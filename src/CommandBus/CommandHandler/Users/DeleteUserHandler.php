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

use eTraxis\CommandBus\Command\Users\DeleteUserCommand;
use eTraxis\Repository\UserRepository;
use eTraxis\Voter\UserVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteUserHandler
{
    protected $security;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepository                $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, UserRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteUserCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteUserCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->find($command->user);

        if ($user) {

            if (!$this->security->isGranted(UserVoter::DELETE_USER, $user)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($user);
        }
    }
}
