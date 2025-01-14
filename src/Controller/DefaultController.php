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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Default controller.
 */
class DefaultController extends AbstractController
{
    /**
     * Default page of public area.
     *
     * @Route("/", name="homepage")
     *
     * @return Response
     */
    public function homepage(): Response
    {
        return $this->render('base.html.twig');
    }

    /**
     * Default page of admin area.
     *
     * @Route("/admin/", name="admin")
     *
     * @return Response
     */
    public function admin(): Response
    {
        return $this->redirectToRoute('admin_users');
    }
}
