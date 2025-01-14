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
 * @covers \eTraxis\CommandBus\CommandHandler\Users\UnlockUserHandler::handle
 */
class UnlockUserCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testUnlockUser()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('jgutmann@example.com');
        self::assertFalse($user->isAccountNonLocked());

        $command = new UnlockUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);
        self::assertTrue($user->isAccountNonLocked());
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('jgutmann@example.com');

        $command = new UnlockUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UnlockUserCommand([
            'user' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }
}
