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
use eTraxis\CommandBus\CommandHandler\Fields\HandlerTrait\DurationHandlerTrait;
use eTraxis\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @coversDefaultClass \eTraxis\CommandBus\CommandHandler\Fields\HandlerTrait\DurationHandlerTrait
 */
class DurationHandlerTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Contracts\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\Repository\Contracts\FieldRepositoryInterface */
    protected $repository;

    /** @var DurationHandlerTrait $handler */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);

        $this->handler = new class() {
            use DurationHandlerTrait;
        };
    }

    /**
     * @covers ::getSupportedFieldType
     */
    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::DURATION, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldSuccess()
    {
        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        /** @var \eTraxis\Entity\FieldTypes\DurationInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame('0:00', $facade->getMinimumValue());
        self::assertSame('999999:59', $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new Command\UpdateDurationFieldCommand([
            'minimum' => '0:01',
            'maximum' => '0:59',
            'default' => '0:30',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertSame('0:01', $facade->getMinimumValue());
        self::assertSame('0:59', $facade->getMaximumValue());
        self::assertSame('0:30', $facade->getDefaultValue());
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldMinMaxValuesError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Maximum value should not be less then minimum one.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new Command\UpdateDurationFieldCommand([
            'minimum' => '0:59',
            'maximum' => '0:01',
            'default' => '0:30',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    /**
     * @covers ::copyCommandToField
     */
    public function testCopyCommandToFieldDefaultValueRangeError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should be in range from 0:01 to 0:59.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new Command\UpdateDurationFieldCommand([
            'minimum' => '0:01',
            'maximum' => '0:59',
            'default' => '0:00',
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
        [$field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

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

        $command = new Command\UpdateDurationFieldCommand([
            'minimum' => '0:01',
            'maximum' => '0:59',
            'default' => '0:30',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
