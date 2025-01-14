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

namespace eTraxis\CommandBus\Command\Groups;

use eTraxis\Entity\Group;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Groups\DeleteGroupHandler::handle
 */
class DeleteGroupCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\GroupRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);
        self::assertNotNull($group);

        $command = new DeleteGroupCommand([
            'group' => $group->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $group = $this->repository->find($command->group);
        self::assertNull($group);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteGroupCommand([
            'group' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new DeleteGroupCommand([
            'group' => $group->id,
        ]);

        $this->commandBus->handle($command);
    }
}
