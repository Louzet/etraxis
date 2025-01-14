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

namespace eTraxis\Controller\ApiMyController;

use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ApiMyController::updateProfile
 */
class UpdateProfileTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        self::assertSame('nhills@example.com', $user->email);
        self::assertSame('Nikko Hills', $user->fullname);
        self::assertSame('en_US', $user->locale);
        self::assertSame('azure', $user->theme);
        self::assertSame('UTC', $user->timezone);

        $uri = '/api/my/profile';

        $data = [
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
            'locale'   => 'es',
            'theme'    => 'humanity',
            'timezone' => 'Europe/Madrid',
        ];

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('chaim.willms@example.com', $user->email);
        self::assertSame('Chaim Willms', $user->fullname);
        self::assertSame('es', $user->locale);
        self::assertSame('humanity', $user->theme);
        self::assertSame('Europe/Madrid', $user->timezone);
    }

    public function testSuccessPartial()
    {
        $this->loginAs('einstein@ldap.forumsys.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'einstein@ldap.forumsys.com']);

        self::assertSame('einstein@ldap.forumsys.com', $user->email);
        self::assertSame('Albert Einstein', $user->fullname);
        self::assertSame('en_US', $user->locale);
        self::assertSame('azure', $user->theme);
        self::assertSame('UTC', $user->timezone);

        $uri = '/api/my/profile';

        $data = [
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
            'locale'   => 'de',
            'theme'    => 'humanity',
            'timezone' => 'Europe/Berlin',
        ];

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('einstein@ldap.forumsys.com', $user->email);
        self::assertSame('Albert Einstein', $user->fullname);
        self::assertSame('de', $user->locale);
        self::assertSame('humanity', $user->theme);
        self::assertSame('Europe/Berlin', $user->timezone);
    }

    public function test400()
    {
        $this->loginAs('nhills@example.com');

        $uri = '/api/my/profile';

        $data = [
            'email'    => 'invalid',
            'fullname' => 'Chaim Willms',
            'locale'   => 'es',
            'theme'    => 'humanity',
            'timezone' => 'Europe/Madrid',
        ];

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        $uri = '/api/my/profile';

        $response = $this->json(Request::METHOD_PATCH, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('nhills@example.com');

        $uri = '/api/my/profile';

        $data = [
            'email'    => 'ldoyle@example.com',
            'fullname' => 'Chaim Willms',
            'locale'   => 'es',
            'theme'    => 'humanity',
            'timezone' => 'Europe/Madrid',
        ];

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
