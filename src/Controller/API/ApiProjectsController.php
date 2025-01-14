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

namespace eTraxis\Controller\API;

use eTraxis\CommandBus\Command\Projects as Command;
use eTraxis\Entity\Project;
use eTraxis\Repository\CollectionTrait;
use eTraxis\Repository\Contracts\ProjectRepositoryInterface;
use eTraxis\Voter\ProjectVoter;
use eTraxis\Voter\TemplateVoter;
use League\Tactician\CommandBus;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * API controller for '/projects' resource.
 *
 * @Route("/api/projects")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="Projects")
 */
class ApiProjectsController extends AbstractController
{
    use CollectionTrait;

    /**
     * Returns list of projects.
     *
     * @Route("", name="api_projects_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first project to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of projects to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="name",        type="string"),
     *         @API\Property(property="description", type="string"),
     *         @API\Property(property="suspended",   type="boolean")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="name",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="description", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="created",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="suspended",   type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned project."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned project."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found projects."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\Project::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request                    $request
     * @param ProjectRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function listProjects(Request $request, ProjectRepositoryInterface $repository): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        return $this->json($collection);
    }

    /**
     * Creates new project.
     *
     * @Route("", name="api_projects_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateProjectCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=409, description="Project with specified name already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createProject(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\CreateProjectCommand($request->request->all());

        /** @var Project $project */
        $project = $commandBus->handle($command);

        $url = $this->generateUrl('api_projects_get', [
            'id' => $project->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified project.
     *
     * @Route("/{id}", name="api_projects_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Project ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Swagger\ProjectEx::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Project is not found.")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function getProject(Project $project): JsonResponse
    {
        $data = $project->jsonSerialize();

        $data[Project::JSON_OPTIONS] = [
            ProjectVoter::UPDATE_PROJECT   => $this->isGranted(ProjectVoter::UPDATE_PROJECT, $project),
            ProjectVoter::DELETE_PROJECT   => $this->isGranted(ProjectVoter::DELETE_PROJECT, $project),
            ProjectVoter::SUSPEND_PROJECT  => $this->isGranted(ProjectVoter::SUSPEND_PROJECT, $project),
            ProjectVoter::RESUME_PROJECT   => $this->isGranted(ProjectVoter::RESUME_PROJECT, $project),
            TemplateVoter::CREATE_TEMPLATE => $this->isGranted(TemplateVoter::CREATE_TEMPLATE, $project),
        ];

        return $this->json($data);
    }

    /**
     * Updates specified project.
     *
     * @Route("/{id}", name="api_projects_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Project ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateProjectCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Project is not found.")
     * @API\Response(response=409, description="Project with specified name already exists.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateProject(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UpdateProjectCommand($request->request->all());

        $command->project = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified project.
     *
     * @Route("/{id}", name="api_projects_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Project ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function deleteProject(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteProjectCommand([
            'project' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Suspends specified project.
     *
     * @Route("/{id}/suspend", name="api_projects_suspend", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Project ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Project is not found.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function suspendProject(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\SuspendProjectCommand([
            'project' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Resumes specified project.
     *
     * @Route("/{id}/resume", name="api_projects_resume", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Project ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Project is not found.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function resumeProject(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\ResumeProjectCommand([
            'project' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }
}
