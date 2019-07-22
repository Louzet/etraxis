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

use eTraxis\CommandBus\Command\Projects\ResumeProjectCommand;
use eTraxis\Repository\ProjectRepository;
use eTraxis\Voter\ProjectVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class ResumeProjectHandler
{
    protected $security;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ProjectRepository             $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, ProjectRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param ResumeProjectCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(ResumeProjectCommand $command)
    {
        /** @var null|\eTraxis\Entity\Project $project */
        $project = $this->repository->find($command->project);

        if (!$project) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ProjectVoter::RESUME_PROJECT, $project)) {
            throw new AccessDeniedHttpException();
        }

        if ($project->isSuspended) {

            $project->isSuspended = false;

            $this->repository->persist($project);
        }
    }
}
