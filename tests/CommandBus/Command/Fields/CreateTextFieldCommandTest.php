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
 * @covers \eTraxis\CommandBus\CommandHandler\Fields\CreateTextFieldHandler::handle
 */
class CreateTextFieldCommandTest extends TransactionalTestCase
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Body']);
        self::assertNull($field);

        $command = new CreateTextFieldCommand([
            'state'       => $state->id,
            'name'        => 'Body',
            'required'    => true,
            'maxlength'   => 1000,
            'default'     => 'Message body',
            'pcreCheck'   => '.+',
            'pcreSearch'  => 'search',
            'pcreReplace' => 'replace',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Body']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::TEXT, $field->type);

        /** @var \eTraxis\Entity\FieldTypes\TextInterface $facade */
        $facade = $field->getFacade($this->manager);
        self::assertSame(1000, $facade->getMaximumLength());
        self::assertSame('Message body', $facade->getDefaultValue());
        self::assertSame('.+', $facade->getPCRE()->check);
        self::assertSame('search', $facade->getPCRE()->search);
        self::assertSame('replace', $facade->getPCRE()->replace);
    }
}
