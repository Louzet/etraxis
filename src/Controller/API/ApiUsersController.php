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

use eTraxis\CommandBus\Command\Users as Command;
use eTraxis\Entity\User;
use eTraxis\Repository\CollectionTrait;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Voter\UserVoter;
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
 * API controller for '/users' resource.
 *
 * @Route("/api/users")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="Users")
 */
class ApiUsersController extends AbstractController
{
    use CollectionTrait;

    /**
     * Returns list of users.
     *
     * @Route("", name="api_users_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first user to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of users to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="email",       type="string"),
     *         @API\Property(property="fullname",    type="string"),
     *         @API\Property(property="description", type="string"),
     *         @API\Property(property="admin",       type="boolean"),
     *         @API\Property(property="disabled",    type="boolean"),
     *         @API\Property(property="locked",      type="boolean"),
     *         @API\Property(property="provider",    type="string", enum={"eTraxis", "LDAP"}, example="LDAP")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="email",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="fullname",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="description", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="admin",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="provider",    type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned user."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned user."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found users."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\User::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param Request                 $request
     * @param UserRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function listUsers(Request $request, UserRepositoryInterface $repository): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        return $this->json($collection);
    }

    /**
     * Creates new user.
     *
     * @Route("", name="api_users_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateUserCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=409, description="Account with specified email already exists.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createUser(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\CreateUserCommand($request->request->all());

        /** @var User $user */
        $user = $commandBus->handle($command);

        $url = $this->generateUrl('api_users_get', [
            'id' => $user->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified user.
     *
     * @Route("/{id}", name="api_users_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Swagger\UserEx::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function retrieveUser(User $user): JsonResponse
    {
        $data = $user->jsonSerialize();

        $data[User::JSON_OPTIONS] = [
            UserVoter::UPDATE_USER  => $this->isGranted(UserVoter::UPDATE_USER, $user),
            UserVoter::DELETE_USER  => $this->isGranted(UserVoter::DELETE_USER, $user),
            UserVoter::DISABLE_USER => $this->isGranted(UserVoter::DISABLE_USER, $user),
            UserVoter::ENABLE_USER  => $this->isGranted(UserVoter::ENABLE_USER, $user),
            UserVoter::UNLOCK_USER  => $this->isGranted(UserVoter::UNLOCK_USER, $user),
            UserVoter::SET_PASSWORD => $this->isGranted(UserVoter::SET_PASSWORD, $user),
        ];

        return $this->json($data);
    }

    /**
     * Updates specified user.
     *
     * @Route("/{id}", name="api_users_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateUserCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     * @API\Response(response=409, description="Account with specified email already exists.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateUser(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UpdateUserCommand($request->request->all());

        $command->user = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified user.
     *
     * @Route("/{id}", name="api_users_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
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
    public function deleteUser(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteUserCommand([
            'user' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Sets password for the specified user.
     *
     * @Route("/{id}/password", name="api_users_password", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\SetPasswordCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function setPassword(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\SetPasswordCommand($request->request->all());

        $command->user = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Unlocks specified user.
     *
     * @Route("/{id}/unlock", name="api_users_unlock", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function unlockUser(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UnlockUserCommand([
            'user' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns groups for the specified user.
     *
     * @Route("/{id}/groups", name="api_users_groups_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Swagger\Group::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function getGroups(User $user): JsonResponse
    {
        return $this->json($user->groups);
    }

    /**
     * Sets groups for the specified user.
     *
     * @Route("/{id}/groups", name="api_users_groups_set", methods={"PATCH"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="User ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     @API\Property(property="add", type="array", example={123, 456}, description="List of group IDs to add.",
     *         @API\Items(type="integer")
     *     ),
     *     @API\Property(property="remove", type="array", example={123, 456}, description="List of group IDs to remove.",
     *         @API\Items(type="integer")
     *     )
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function setGroups(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $add    = $request->request->get('add');
        $remove = $request->request->get('remove');

        $add    = is_array($add) ? $add : [];
        $remove = is_array($remove) ? $remove : [];

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();
        $manager->beginTransaction();

        $command = new Command\AddGroupsCommand([
            'user'   => $id,
            'groups' => array_diff($add, $remove),
        ]);

        if (count($command->groups)) {
            $commandBus->handle($command);
        }

        $command = new Command\RemoveGroupsCommand([
            'user'   => $id,
            'groups' => array_diff($remove, $add),
        ]);

        if (count($command->groups)) {
            $commandBus->handle($command);
        }

        $manager->commit();

        return $this->json(null);
    }

    /**
     * Disables specified users.
     *
     * @Route("/disable", name="api_users_disable", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\DisableUsersCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function disableUsers(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DisableUsersCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Enables specified users.
     *
     * @Route("/enable", name="api_users_enable", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\EnableUsersCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="User is not found.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function enableUsers(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\EnableUsersCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }
}
