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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Fields\HandlerTrait;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DurationCommandTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait DurationHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::DURATION;
    }

    /**
     * {@inheritdoc}
     *
     * @param DurationCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(DurationCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DurationInterface $facade */
        $facade = $field->getFacade($manager);

        $minimumValue = $facade->toNumber($command->minimumValue);
        $maximumValue = $facade->toNumber($command->maximumValue);

        if ($minimumValue > $maximumValue) {
            throw new BadRequestHttpException($translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            $default = $facade->toNumber($command->defaultValue);

            if ($default < $minimumValue || $default > $maximumValue) {

                $message = $translator->trans('field.error.default_value_range', [
                    '%minimum%' => $command->minimumValue,
                    '%maximum%' => $command->maximumValue,
                ]);

                throw new BadRequestHttpException($message);
            }
        }

        $facade->setMinimumValue($command->minimumValue);
        $facade->setMaximumValue($command->maximumValue);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }
}
