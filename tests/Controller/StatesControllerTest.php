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

use eTraxis\Entity\State;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \eTraxis\Controller\StatesController
 */
class StatesControllerTest extends WebTestCase
{
    /**
     * @covers ::actions
     */
    public function testActions()
    {
        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $expected = [
            'update'       => true,
            'delete'       => false,
            'initial'      => true,
            'transitions'  => true,
            'responsibles' => false,
        ];

        $uri = sprintf('/admin/states/actions/%s', $state->id);

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $this->loginAs('artem@example.com');

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->loginAs('admin@example.com');

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }
}
