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
use eTraxis\CommandBus\Command\Fields\CommandTrait\DecimalCommandTrait;
use eTraxis\Dictionary\FieldType;
use eTraxis\Entity\DecimalValue;
use eTraxis\Entity\Field;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait DecimalHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::DECIMAL;
    }

    /**
     * {@inheritdoc}
     *
     * @param DecimalCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(DecimalCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\Entity\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($manager);

        if (bccomp($command->minimum, $command->maximum, DecimalValue::PRECISION) > 0) {
            throw new BadRequestHttpException($translator->trans('field.error.min_max_values'));
        }

        if ($command->default !== null) {

            if (bccomp($command->default, $command->minimum, DecimalValue::PRECISION) < 0 ||
                bccomp($command->default, $command->maximum, DecimalValue::PRECISION) > 0)
            {

                $message = $translator->trans('field.error.default_value_range', [
                    '%minimum%' => $command->minimum,
                    '%maximum%' => $command->maximum,
                ]);

                throw new BadRequestHttpException($message);
            }
        }

        $facade->setMinimumValue($command->minimum);
        $facade->setMaximumValue($command->maximum);
        $facade->setDefaultValue($command->default);

        return $field;
    }
}
