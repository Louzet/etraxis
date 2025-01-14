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

use eTraxis\CommandBus\Command\ListItems as Command;
use eTraxis\Entity\ListItem;
use eTraxis\Voter\ListItemVoter;
use League\Tactician\CommandBus;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for '/items' resource.
 *
 * @Route("/api/items")
 * @Security("is_granted('ROLE_ADMIN')")
 *
 * @API\Tag(name="List Items")
 */
class ApiItemsController extends AbstractController
{
    /**
     * Returns specified list item.
     *
     * @Route("/{id}", name="api_items_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Item ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Swagger\ListItemEx::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Item is not found.")
     *
     * @param ListItem $item
     *
     * @return JsonResponse
     */
    public function getItem(ListItem $item): JsonResponse
    {
        $data = $item->jsonSerialize();

        $data[ListItem::JSON_OPTIONS] = [
            ListItemVoter::UPDATE_ITEM => $this->isGranted(ListItemVoter::UPDATE_ITEM, $item),
            ListItemVoter::DELETE_ITEM => $this->isGranted(ListItemVoter::DELETE_ITEM, $item),
        ];

        return $this->json($data);
    }

    /**
     * Updates specified list item.
     *
     * @Route("/{id}", name="api_items_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Item ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateListItemCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Item is not found.")
     * @API\Response(response=409, description="Item with specified value or text already exists.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateItem(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UpdateListItemCommand($request->request->all());

        $command->item = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified list item.
     *
     * @Route("/{id}", name="api_items_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Item ID.")
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
    public function deleteItem(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteListItemCommand([
            'item' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }
}
