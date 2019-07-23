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

namespace eTraxis\CommandBus\CommandHandler\States;

use eTraxis\CommandBus\Command\States\DeleteStateCommand;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Voter\StateVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteStateHandler
{
    protected $security;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param StateRepositoryInterface      $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, StateRepositoryInterface $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteStateCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteStateCommand $command): void
    {
        /** @var null|\eTraxis\Entity\State $state */
        $state = $this->repository->find($command->state);

        if ($state) {

            if (!$this->security->isGranted(StateVoter::DELETE_STATE, $state)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($state);
        }
    }
}
