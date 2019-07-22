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

/** @noinspection PhpUnusedPrivateMethodInspection */

namespace eTraxis\CommandBus\CommandHandler\Fields\HandlerTrait;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\CommandBus\Command\Fields\AbstractFieldCommand;
use eTraxis\CommandBus\Command\Fields\AbstractUpdateFieldCommand;
use eTraxis\CommandBus\Command\Fields\CommandTrait\ListCommandTrait;
use eTraxis\Dictionary\FieldType;
use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait ListHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::LIST;
    }

    /**
     * {@inheritdoc}
     *
     * @param ListCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(ListCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\Entity\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($manager);

        if (get_parent_class($command) === AbstractUpdateFieldCommand::class) {

            /** @var \eTraxis\CommandBus\Command\Fields\UpdateListFieldCommand $command */
            if ($command->default === null) {
                $facade->setDefaultValue(null);
            }
            else {
                /** @var null|ListItem $item */
                $item = $manager->getRepository(ListItem::class)->find($command->default);

                if (!$item || $item->field !== $field) {
                    throw new NotFoundHttpException();
                }

                $facade->setDefaultValue($item);
            }
        }

        return $field;
    }
}
