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

namespace eTraxis\CommandBus\Command\Fields;

use eTraxis\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\State;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Fields\CreateIssueFieldHandler::handle
 */
class CreateIssueFieldCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\FieldRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Request ID']);
        self::assertNull($field);

        $command = new CreateIssueFieldCommand([
            'state'    => $state->id,
            'name'     => 'Request ID',
            'required' => true,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Request ID']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::ISSUE, $field->type);
    }
}
