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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Fields;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractUpdateFieldCommand;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Abstract "Create/update field" command handler.
 */
class AbstractFieldHandler
{
    protected $translator;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param TranslatorInterface    $translator
     * @param EntityManagerInterface $manager
     */
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $manager)
    {
        $this->translator = $translator;
        $this->manager    = $manager;
    }

    /**
     * Copies field-specific parameters from create/update command to specified field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    protected function copyCommandToField(AbstractFieldCommand $command, Field $field): Field
    {
        $handlers = [
            FieldType::CHECKBOX => 'copyAsCheckbox',
            FieldType::DATE     => 'copyAsDate',
            FieldType::DECIMAL  => 'copyAsDecimal',
            FieldType::DURATION => 'copyAsDuration',
            FieldType::ISSUE    => 'copyAsIssue',
            FieldType::LIST     => 'copyAsList',
            FieldType::NUMBER   => 'copyAsNumber',
            FieldType::STRING   => 'copyAsString',
            FieldType::TEXT     => 'copyAsText',
        ];

        $handler = $handlers[$field->type];

        return $this->{$handler}($command, $field);
    }

    /**
     * Copies field-specific parameters from create/update command to specified "checkbox" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsCheckbox(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\CheckboxCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\CheckboxInterface $facade */
        $facade = $field->getFacade($this->manager);

        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "date" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsDate(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DateCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DateInterface $facade */
        $facade = $field->getFacade($this->manager);

        if ($command->minimumValue > $command->maximumValue) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            if ($command->defaultValue < $command->minimumValue || $command->defaultValue > $command->maximumValue) {

                $message = $this->translator->trans('field.error.default_value_range', [
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

    /**
     * Copies field-specific parameters from create/update command to specified "decimal" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsDecimal(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DecimalCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($this->manager);

        if (bccomp($command->minimumValue, $command->maximumValue, DecimalValue::PRECISION) > 0) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            if (bccomp($command->defaultValue, $command->minimumValue, DecimalValue::PRECISION) < 0 ||
                bccomp($command->defaultValue, $command->maximumValue, DecimalValue::PRECISION) > 0)
            {

                $message = $this->translator->trans('field.error.default_value_range', [
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

    /**
     * Copies field-specific parameters from create/update command to specified "duration" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsDuration(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DurationCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DurationInterface $facade */
        $facade = $field->getFacade($this->manager);

        $minimumValue = $facade->toNumber($command->minimumValue);
        $maximumValue = $facade->toNumber($command->maximumValue);

        if ($minimumValue > $maximumValue) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            $default = $facade->toNumber($command->defaultValue);

            if ($default < $minimumValue || $default > $maximumValue) {

                $message = $this->translator->trans('field.error.default_value_range', [
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

    /**
     * Copies field-specific parameters from create/update command to specified "issue" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsIssue(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\IssueCommandTrait $command */

        // NOP

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "list" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsList(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\ListCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($this->manager);

        if (get_parent_class($command) === AbstractUpdateFieldCommand::class) {

            /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\UpdateListFieldCommand $command */
            if ($command->defaultValue === null) {
                $facade->setDefaultValue(null);
            }
            else {
                /** @var null|\eTraxis\TemplatesDomain\Model\Entity\ListItem $item */
                $item = $this->manager->getRepository(ListItem::class)->find($command->defaultValue);

                if (!$item || $item->field !== $field) {
                    throw new NotFoundHttpException();
                }

                $facade->setDefaultValue($item);
            }
        }

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "number" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsNumber(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\NumberCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\NumberInterface $facade */
        $facade = $field->getFacade($this->manager);

        if ($command->minimumValue > $command->maximumValue) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            if ($command->defaultValue < $command->minimumValue || $command->defaultValue > $command->maximumValue) {

                $message = $this->translator->trans('field.error.default_value_range', [
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

    /**
     * Copies field-specific parameters from create/update command to specified "string" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsString(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\StringCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\StringInterface $facade */
        $facade = $field->getFacade($this->manager);

        $pcre = $facade->getPCRE();

        $pcre->check   = $command->pcreCheck;
        $pcre->search  = $command->pcreSearch;
        $pcre->replace = $command->pcreReplace;

        if (mb_strlen($command->defaultValue) > $command->maximumLength) {

            $message = $this->translator->trans('field.error.default_value_length', [
                '%maximum%' => $command->maximumLength,
            ]);

            throw new BadRequestHttpException($message);
        }

        if (!$pcre->validate($command->defaultValue)) {
            throw new BadRequestHttpException($this->translator->trans('field.error.default_value_format'));
        }

        $facade->setMaximumLength($command->maximumLength);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "text" field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    private function copyAsText(AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\TextCommandTrait $command */
        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\TextInterface $facade */
        $facade = $field->getFacade($this->manager);

        $pcre = $facade->getPCRE();

        $pcre->check   = $command->pcreCheck;
        $pcre->search  = $command->pcreSearch;
        $pcre->replace = $command->pcreReplace;

        if (mb_strlen($command->defaultValue) > $command->maximumLength) {

            $message = $this->translator->trans('field.error.default_value_length', [
                '%maximum%' => $command->maximumLength,
            ]);

            throw new BadRequestHttpException($message);
        }

        if (!$pcre->validate($command->defaultValue)) {
            throw new BadRequestHttpException($this->translator->trans('field.error.default_value_format'));
        }

        $facade->setMaximumLength($command->maximumLength);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }
}
