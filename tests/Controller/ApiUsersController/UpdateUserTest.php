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
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ApiUsersController::updateUser
 */
class UpdateUserTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'email'    => 'chaim.willms@example.com',
            'fullname' => $user->fullname,
            'admin'    => $user->isAdmin,
            'disabled' => !$user->isEnabled(),
            'locale'   => $user->locale,
            'theme'    => $user->theme,
            'timezone' => $user->timezone,
        ];

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('chaim.willms@example.com', $user->email);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'email'    => 'chaim.willms@example.com',
            'fullname' => $user->fullname,
            'admin'    => $user->isAdmin,
            'disabled' => !$user->isEnabled(),
            'locale'   => $user->locale,
            'theme'    => $user->theme,
            'timezone' => $user->timezone,
        ];

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'email'    => 'chaim.willms@example.com',
            'fullname' => $user->fullname,
            'admin'    => $user->isAdmin,
            'disabled' => !$user->isEnabled(),
            'locale'   => $user->locale,
            'theme'    => $user->theme,
            'timezone' => $user->timezone,
        ];

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'email'    => 'chaim.willms@example.com',
            'fullname' => $user->fullname,
            'admin'    => $user->isAdmin,
            'disabled' => !$user->isEnabled(),
            'locale'   => $user->locale,
            'theme'    => $user->theme,
            'timezone' => $user->timezone,
        ];

        $uri = sprintf('/api/users/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'email'    => 'vparker@example.com',
            'fullname' => $user->fullname,
            'admin'    => $user->isAdmin,
            'disabled' => !$user->isEnabled(),
            'locale'   => $user->locale,
            'theme'    => $user->theme,
            'timezone' => $user->timezone,
        ];

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
