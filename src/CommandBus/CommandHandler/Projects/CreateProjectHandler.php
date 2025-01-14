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

namespace eTraxis\CommandBus\CommandHandler\Projects;

use eTraxis\CommandBus\Command\Projects\CreateProjectCommand;
use eTraxis\Entity\Project;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use eTraxis\Voter\ProjectVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateProjectHandler
{
    protected $security;
    protected $validator;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ProjectRepositoryInterface    $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ProjectRepositoryInterface    $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param CreateProjectCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     *
     * @return Project
     */
    public function handle(CreateProjectCommand $command): Project
    {
        if (!$this->security->isGranted(ProjectVoter::CREATE_PROJECT)) {
            throw new AccessDeniedHttpException();
        }

        $project = new Project();

        $project->name        = $command->name;
        $project->description = $command->description;
        $project->isSuspended = $command->suspended;

        $errors = $this->validator->validate($project);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($project);

        return $project;
    }
}
