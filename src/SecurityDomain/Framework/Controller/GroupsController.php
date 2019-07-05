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

namespace eTraxis\SecurityDomain\Framework\Controller;

use eTraxis\SecurityDomain\Application\Voter\GroupVoter;
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SharedDomain\Model\Collection\CollectionInterface;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Repository\ProjectRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Groups controller.
 *
 * @Route("/admin/groups")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class GroupsController extends AbstractController
{
    /**
     * 'Groups' page.
     *
     * @Route("", name="admin_groups", methods={"GET"})
     *
     * @param ProjectRepository $repository
     *
     * @return Response
     */
    public function index(ProjectRepository $repository): Response
    {
        $projects = $repository->findBy([], [
            Project::JSON_NAME => CollectionInterface::SORT_ASC,
        ]);

        return $this->render('groups/index.html.twig', [
            'projects' => $projects,
            'can'      => [
                'create' => $this->isGranted(GroupVoter::CREATE_GROUP),
            ],
        ]);
    }

    /**
     * A group page.
     *
     * @Route("/{id}", name="admin_view_group", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param Group $group
     *
     * @return Response
     */
    public function view(Group $group): Response
    {
        return $this->render('groups/view.html.twig', [
            'group' => $group,
            'can'   => [
                'update'     => $this->isGranted(GroupVoter::UPDATE_GROUP, $group),
                'delete'     => $this->isGranted(GroupVoter::DELETE_GROUP, $group),
                'membership' => $this->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group),
            ],
        ]);
    }
}
