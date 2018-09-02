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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiGroupsController;

use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListGroupsTest extends WebTestCase
{
    public function testSuccess()
    {
        $expected = [
            ['Managers',          'Managers A'],
            ['Developers',        'Developers A'],
            ['Clients',           'Clients A'],
            ['Support Engineers', 'Support Engineers A'],
            ['Managers',          'Managers B'],
            ['Developers',        'Developers B'],
            ['Clients',           'Clients B'],
            ['Support Engineers', 'Support Engineers B'],
            ['Managers',          'Managers C'],
            ['Developers',        'Developers C'],
            ['Clients',           'Clients C'],
            ['Support Engineers', 'Support Engineers C'],
            ['Managers',          'Managers D'],
            ['Developers',        'Developers D'],
            ['Clients',           'Clients D'],
            ['Support Engineers', 'Support Engineers D'],
            ['Company Staff',     null],
            ['Company Clients',   null],
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $actual  = array_map(function (array $row) {
            return [$row['name'], $row['description']];
        }, $content['data']);

        self::assertSame(0, $content['from']);
        self::assertSame(17, $content['to']);
        self::assertSame(18, $content['total']);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}