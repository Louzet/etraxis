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

namespace eTraxis\CommandBus\Command\States;

use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\States\DeleteStateHandler::handle
 */
class DeleteStateCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\StateRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);
        self::assertNotNull($state);

        $command = new DeleteStateCommand([
            'state' => $state->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $state = $this->repository->find($command->state);
        self::assertNull($state);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteStateCommand([
            'state' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'DESC']);

        $command = new DeleteStateCommand([
            'state' => $state->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'DESC']);

        $command = new DeleteStateCommand([
            'state' => $state->id,
        ]);

        $this->commandBus->handle($command);
    }
}
