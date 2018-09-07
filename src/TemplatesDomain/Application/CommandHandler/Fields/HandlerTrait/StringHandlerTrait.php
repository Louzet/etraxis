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
use eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\StringCommandTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait StringHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::STRING;
    }

    /**
     * {@inheritdoc}
     *
     * @param StringCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(StringCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\StringInterface $facade */
        $facade = $field->getFacade($manager);

        $pcre = $facade->getPCRE();

        $pcre->check   = $command->pcreCheck;
        $pcre->search  = $command->pcreSearch;
        $pcre->replace = $command->pcreReplace;

        if (mb_strlen($command->defaultValue) > $command->maximumLength) {

            $message = $translator->trans('field.error.default_value_length', [
                '%maximum%' => $command->maximumLength,
            ]);

            throw new BadRequestHttpException($message);
        }

        if (!$pcre->validate($command->defaultValue)) {
            throw new BadRequestHttpException($translator->trans('field.error.default_value_format'));
        }

        $facade->setMaximumLength($command->maximumLength);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }
}
