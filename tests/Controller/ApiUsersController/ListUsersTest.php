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

namespace eTraxis\Controller\ApiUsersController;

use eTraxis\Entity\User;
use eTraxis\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ApiUsersController::listUsers
 */
class ListUsersTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $expected = array_map(function (User $user) {
            return $user->fullname;
        }, $this->doctrine->getRepository(User::class)->findAll());

        $uri = '/api/users';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $actual  = array_map(function (array $row) {
            return $row['fullname'];
        }, $content['data']);

        self::assertSame(0, $content['from']);
        self::assertSame(34, $content['to']);
        self::assertSame(35, $content['total']);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/users';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $uri = '/api/users';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
