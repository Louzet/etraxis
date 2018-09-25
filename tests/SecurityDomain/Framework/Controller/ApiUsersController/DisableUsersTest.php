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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiUsersController;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableUsersTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $nhills */
        /** @var User $tberge */
        $nhills = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);
        $tberge = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tberge@example.com']);

        self::assertTrue($nhills->isEnabled());
        self::assertFalse($tberge->isEnabled());

        $data = [
            'users' => [
                $nhills->id,
                $tberge->id,
            ],
        ];

        $uri = '/api/users/disable';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($nhills);
        $this->doctrine->getManager()->refresh($tberge);

        self::assertFalse($nhills->isEnabled());
        self::assertFalse($tberge->isEnabled());
    }

    public function test401()
    {
        /** @var User $nhills */
        /** @var User $tberge */
        $nhills = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);
        $tberge = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tberge@example.com']);

        $data = [
            'users' => [
                $nhills->id,
                $tberge->id,
            ],
        ];

        $uri = '/api/users/disable';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var User $nhills */
        /** @var User $tberge */
        $nhills = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);
        $tberge = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tberge@example.com']);

        $data = [
            'users' => [
                $nhills->id,
                $tberge->id,
            ],
        ];

        $uri = '/api/users/disable';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var User $nhills */
        /** @var User $tberge */
        $nhills = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);
        $tberge = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tberge@example.com']);

        $data = [
            'users' => [
                $nhills->id,
                $tberge->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $uri = '/api/users/disable';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
