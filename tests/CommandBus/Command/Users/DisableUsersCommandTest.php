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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Users\DisableUsersHandler::handle
 */
class DisableUsersCommandTest extends TransactionalTestCase
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

        /** @var User $nhills */
        /** @var User $tberge */
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $tberge = $this->repository->findOneByUsername('tberge@example.com');

        self::assertTrue($nhills->isEnabled());
        self::assertFalse($tberge->isEnabled());

        $command = new DisableUsersCommand([
            'users' => [
                $nhills->id,
                $tberge->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($nhills);
        $this->doctrine->getManager()->refresh($tberge);

        self::assertFalse($nhills->isEnabled());
        self::assertFalse($tberge->isEnabled());
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('nhills@example.com');

        $command = new DisableUsersCommand([
            'users' => [
                $user->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new DisableUsersCommand([
            'users' => [
                self::UNKNOWN_ENTITY_ID,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testForbidden()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var User $admin */
        $admin = $this->repository->findOneByUsername('admin@example.com');

        $command = new DisableUsersCommand([
            'users' => [
                $admin->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }
}
