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

use eTraxis\CommandBus\Command\Templates as Command;
use eTraxis\Entity\Template;
use eTraxis\Repository\CollectionTrait;
use eTraxis\Repository\Contracts\TemplateRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use eTraxis\Voter\StateVoter;
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
 * API controller for '/templates' resource.
 *
 * @Route("/api/templates")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="Templates")
 */
class ApiTemplatesController extends AbstractController
{
    use CollectionTrait;

    /**
     * Returns list of templates.
     *
     * @Route("", name="api_templates_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first template to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of templates to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="project",     type="integer"),
     *         @API\Property(property="name",        type="string"),
     *         @API\Property(property="prefix",      type="string"),
     *         @API\Property(property="description", type="string"),
     *         @API\Property(property="critical",    type="integer"),
     *         @API\Property(property="frozen",      type="integer"),
     *         @API\Property(property="locked",      type="boolean")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="name",        type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="prefix",      type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="description", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="critical",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="frozen",      type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="locked",      type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned template."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned template."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found templates."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\Template::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request                     $request
     * @param TemplateRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function listTemplates(Request $request, TemplateRepositoryInterface $repository): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        return $this->json($collection);
    }

    /**
     * Creates new template.
     *
     * @Route("", name="api_templates_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateTemplateCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Project is not found.")
     * @API\Response(response=409, description="Template with specified name or prefix already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createTemplate(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\CreateTemplateCommand($request->request->all());

        /** @var Template $template */
        $template = $commandBus->handle($command);

        $url = $this->generateUrl('api_templates_get', [
            'id' => $template->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified template.
     *
     * @Route("/{id}", name="api_templates_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Template ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Swagger\TemplateEx::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     *
     * @param Template $template
     *
     * @return JsonResponse
     */
    public function getTemplate(Template $template): JsonResponse
    {
        $data = $template->jsonSerialize();

        $data[Template::JSON_OPTIONS] = [
            TemplateVoter::UPDATE_TEMPLATE    => $this->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template),
            TemplateVoter::DELETE_TEMPLATE    => $this->isGranted(TemplateVoter::DELETE_TEMPLATE, $template),
            TemplateVoter::LOCK_TEMPLATE      => $this->isGranted(TemplateVoter::LOCK_TEMPLATE, $template),
            TemplateVoter::UNLOCK_TEMPLATE    => $this->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $template),
            TemplateVoter::MANAGE_PERMISSIONS => $this->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $template),
            StateVoter::CREATE_STATE          => $this->isGranted(StateVoter::CREATE_STATE, $template),
            IssueVoter::CREATE_ISSUE          => $this->isGranted(IssueVoter::CREATE_ISSUE, $template),
        ];

        return $this->json($data);
    }

    /**
     * Updates specified template.
     *
     * @Route("/{id}", name="api_templates_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Template ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateTemplateCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     * @API\Response(response=409, description="Template with specified name or prefix already exists.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateTemplate(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UpdateTemplateCommand($request->request->all());

        $command->template = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified template.
     *
     * @Route("/{id}", name="api_templates_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Template ID.")
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
    public function deleteTemplate(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteTemplateCommand([
            'template' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Locks specified template.
     *
     * @Route("/{id}/lock", name="api_templates_lock", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Template ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function lockTemplate(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\LockTemplateCommand([
            'template' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Unlocks specified template.
     *
     * @Route("/{id}/unlock", name="api_templates_suspend", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Template ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function unlockTemplate(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UnlockTemplateCommand([
            'template' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns permissions of specified template.
     *
     * @Route("/{id}/permissions", name="api_templates_get_permissions", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Template ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="roles", type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\TemplateRolePermission::class)
     *         )),
     *         @API\Property(property="groups", type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\TemplateGroupPermission::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     *
     * @param Template $template
     *
     * @return JsonResponse
     */
    public function getPermissions(Template $template): JsonResponse
    {
        return $this->json([
            'roles'  => $template->rolePermissions,
            'groups' => $template->groupPermissions,
        ]);
    }

    /**
     * Sets permissions of specified template.
     *
     * @Route("/{id}/permissions", name="api_templates_set_permissions", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Template ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     type="object",
     *     required={"permission"},
     *     properties={
     *         @API\Property(property="permission", type="string", enum={
     *             "comment.add",
     *             "comment.private",
     *             "dependency.add",
     *             "dependency.remove",
     *             "file.attach",
     *             "file.delete",
     *             "issue.create",
     *             "issue.delete",
     *             "issue.edit",
     *             "issue.reassign",
     *             "issue.reopen",
     *             "issue.resume",
     *             "issue.suspend",
     *             "issue.view",
     *             "reminder.send"
     *         }, example="issue.edit", description="Specific permission."),
     *         @API\Property(property="roles",  type="array", @API\Items(type="string", enum={"anyone", "author", "responsible"}, example="author", description="System role.")),
     *         @API\Property(property="groups", type="array", @API\Items(type="integer", example=123, description="Group ID."))
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function setPermissions(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $permission = $request->get('permission');
        $roles      = $request->get('roles');
        $groups     = $request->get('groups');

        if ($roles !== null) {

            $command = new Command\SetRolesPermissionCommand([
                'template'   => $id,
                'permission' => $permission,
                'roles'      => $roles,
            ]);

            $commandBus->handle($command);
        }

        if ($groups !== null) {

            $command = new Command\SetGroupsPermissionCommand([
                'template'   => $id,
                'permission' => $permission,
                'groups'     => $groups,
            ]);

            $commandBus->handle($command);
        }

        return $this->json(null);
    }
}
