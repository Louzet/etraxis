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

namespace eTraxis\CommandBus\Command\Users;

use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Users\UpdateUserHandler::handle
 */
class UpdateUserCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('nhills@example.com');

        self::assertSame('Nikko Hills', $user->fullname);
        self::assertNotEmpty($user->description);
        self::assertFalse($user->isAdmin);
        self::assertTrue($user->isEnabled());
        self::assertSame('en_US', $user->locale);
        self::assertSame('azure', $user->theme);
        self::assertSame('UTC', $user->timezone);

        $command = new UpdateUserCommand([
            'user'     => $user->id,
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
            'admin'    => true,
            'disabled' => true,
            'locale'   => 'es',
            'theme'    => 'humanity',
            'timezone' => 'Europe/Madrid',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('chaim.willms@example.com', $user->email);
        self::assertSame('Chaim Willms', $user->fullname);
        self::assertEmpty($user->description);
        self::assertTrue($user->isAdmin);
        self::assertFalse($user->isEnabled());
        self::assertSame('es', $user->locale);
        self::assertSame('humanity', $user->theme);
        self::assertSame('Europe/Madrid', $user->timezone);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('nhills@example.com');

        $command = new UpdateUserCommand([
            'user'        => $user->id,
            'email'       => $user->email,
            'fullname'    => $user->fullname,
            'description' => $user->description,
            'admin'       => $user->isAdmin,
            'disabled'    => !$user->isEnabled(),
            'locale'      => $user->locale,
            'theme'       => $user->theme,
            'timezone'    => $user->timezone,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateUserCommand([
            'user'     => self::UNKNOWN_ENTITY_ID,
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
            'admin'    => true,
            'disabled' => true,
            'locale'   => 'es',
            'theme'    => 'humanity',
            'timezone' => 'Europe/Madrid',
        ]);

        $this->commandBus->handle($command);
    }

    public function testUsernameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('nhills@example.com');

        $command = new UpdateUserCommand([
            'user'        => $user->id,
            'email'       => 'vparker@example.com',
            'fullname'    => $user->fullname,
            'description' => $user->description,
            'admin'       => $user->isAdmin,
            'disabled'    => !$user->isEnabled(),
            'locale'      => $user->locale,
            'theme'       => $user->theme,
            'timezone'    => $user->timezone,
        ]);

        $this->commandBus->handle($command);
    }
}
