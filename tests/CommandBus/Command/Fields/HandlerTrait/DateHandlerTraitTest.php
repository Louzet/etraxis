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

namespace eTraxis\CommandBus\Command\Fields\HandlerTrait;

use eTraxis\CommandBus\Command\Fields as Command;
use eTraxis\CommandBus\CommandHandler\Fields\HandlerTrait\DateHandlerTrait;
use eTraxis\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @coversDefaultClass \eTraxis\CommandBus\CommandHandler\Fields\HandlerTrait\DateHandlerTrait
 */
class DateHandlerTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Contracts\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\Repository\Contracts\FieldRepositoryInterface */
    protected $repository;

    /** @var DateHandlerTrait $handler */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);

        $this->handler = new class() {
            use DateHandlerTrait;
        };
    }

    /**
     * @covers ::getSupportedFieldType
     */
    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::DATE, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldSuccess()
    {
        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        /** @var \eTraxis\Entity\FieldTypes\DateInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(0, $facade->getMinimumValue());
        self::assertSame(14, $facade->getMaximumValue());
        self::assertSame(14, $facade->getDefaultValue());

        $command = new Command\UpdateDateFieldCommand([
            'minimum' => '1',
            'maximum' => '7',
            'default' => '3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertSame(1, $facade->getMinimumValue());
        self::assertSame(7, $facade->getMaximumValue());
        self::assertSame(3, $facade->getDefaultValue());
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldMinMaxValuesError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Maximum value should not be less then minimum one.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        $command = new Command\UpdateDateFieldCommand([
            'minimum' => '7',
            'maximum' => '1',
            'default' => '3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldDefaultValueRangeError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should be in range from 1 to 7.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        $command = new Command\UpdateDateFieldCommand([
            'minimum' => '1',
            'maximum' => '7',
            'default' => '0',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldUnsupportedCommand()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported command.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldUnsupportedFieldType()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported field type.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new Command\UpdateDateFieldCommand([
            'minimum' => '1',
            'maximum' => '7',
            'default' => '3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
