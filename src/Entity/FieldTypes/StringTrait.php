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

/** @noinspection PhpUndefinedFieldInspection */

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Entity\Field;
use eTraxis\Entity\FieldParameters;
use eTraxis\Entity\FieldPCRE;
use eTraxis\Repository\Contracts\StringValueRepositoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * String field trait.
 */
trait StringTrait
{
    /**
     * Returns this field as a field of a "string" type.
     *
     * @param StringValueRepositoryInterface $repository
     *
     * @return StringInterface
     */
    protected function asString(StringValueRepositoryInterface $repository): StringInterface
    {
        return new class($repository, $this, $this->pcre, $this->parameters) implements StringInterface {
            protected $repository;
            protected $field;
            protected $pcre;
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param StringValueRepositoryInterface $repository
             * @param Field                          $field
             * @param FieldPCRE                      $pcre
             * @param FieldParameters                $parameters
             */
            public function __construct(StringValueRepositoryInterface $repository, Field $field, FieldPCRE $pcre, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->field      = $field;
                $this->pcre       = $pcre;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function jsonSerialize()
            {
                return [
                    Field::JSON_MAXLENGTH => $this->getMaximumLength(),
                    Field::JSON_DEFAULT   => $this->getDefaultValue(),
                    Field::JSON_PCRE      => $this->getPCRE()->jsonSerialize(),
                ];
            }

            /**
             * {@inheritdoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array
            {
                $constraints = [
                    new Assert\Length([
                        'max' => $this->getMaximumLength(),
                    ]),
                ];

                if ($this->field->isRequired) {
                    $constraints[] = new Assert\NotBlank();
                }

                if ($this->pcre->check) {
                    $constraints[] = new Assert\Regex([
                        'pattern' => sprintf('/^%s$/', $this->pcre->check),
                    ]);
                }

                return $constraints;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumLength(int $length): StringInterface
            {
                if ($length < StringInterface::MIN_LENGTH) {
                    $length = StringInterface::MIN_LENGTH;
                }

                if ($length > StringInterface::MAX_LENGTH) {
                    $length = StringInterface::MAX_LENGTH;
                }

                $this->parameters->parameter1 = $length;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumLength(): int
            {
                return $this->parameters->parameter1 ?? StringInterface::MAX_LENGTH;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?string $value): StringInterface
            {
                if (mb_strlen($value) > StringInterface::MAX_LENGTH) {
                    $value = mb_substr($value, 0, StringInterface::MAX_LENGTH);
                }

                $this->parameters->defaultValue = ($value === null)
                    ? null
                    : $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?string
            {
                if ($this->parameters->defaultValue === null) {
                    return null;
                }

                /** @var \eTraxis\Entity\StringValue $string */
                $string = $this->repository->find($this->parameters->defaultValue);

                return $string->value;
            }

            /**
             * {@inheritdoc}
             */
            public function getPCRE(): FieldPCRE
            {
                return $this->pcre;
            }
        };
    }
}
