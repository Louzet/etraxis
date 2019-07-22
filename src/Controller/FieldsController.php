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

namespace eTraxis\Controller;

use eTraxis\Entity\Field;
use eTraxis\Voter\FieldVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Fields controller.
 *
 * @Route("/admin/fields")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class FieldsController extends AbstractController
{
    /**
     * Returns admin actions available for specified state.
     *
     * @Route("/actions/{id}", name="admin_field_actions", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param Field $field
     *
     * @return JsonResponse
     */
    public function actions(Field $field): JsonResponse
    {
        return $this->json([
            'update'      => $this->isGranted(FieldVoter::UPDATE_FIELD, $field),
            'delete'      => $this->isGranted(FieldVoter::REMOVE_FIELD, $field),
            'permissions' => $this->isGranted(FieldVoter::MANAGE_PERMISSIONS, $field),
        ]);
    }
}
