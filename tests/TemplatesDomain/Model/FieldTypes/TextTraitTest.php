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

namespace eTraxis\TemplatesDomain\Model\FieldTypes;

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\FieldPCRE;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;

class TextTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Contracts\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
    protected $validator;

    /** @var Field */
    protected $object;

    /** @var TextInterface */
    protected $facade;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::TEXT);
        $this->setProperty($this->object, 'id', 1);

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    public function testJsonSerialize()
    {
        $expected = [
            'maxlength' => TextInterface::MAX_LENGTH,
            'default'   => null,
            'pcre'      => [
                'check'   => null,
                'search'  => null,
                'replace' => null,
            ],
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    public function testValidationConstraints()
    {
        $this->facade->setMaximumLength(2000);
        $this->facade->getPCRE()->check = '(\*+)';

        $errors = $this->validator->validate(str_pad(null, 2000, '*'), $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(str_pad(null, 2001, '*'), $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is too long. It should have 2000 characters or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(str_pad(null, 2000, '-'), $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->facade->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
    }

    public function testMaximumLength()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(TextInterface::MIN_LENGTH, TextInterface::MAX_LENGTH);
        $min   = TextInterface::MIN_LENGTH - 1;
        $max   = TextInterface::MAX_LENGTH + 1;

        $this->facade->setMaximumLength($value);
        self::assertSame($value, $this->facade->getMaximumLength());
        self::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $this->facade->setMaximumLength($min);
        self::assertSame(TextInterface::MIN_LENGTH, $this->facade->getMaximumLength());

        $this->facade->setMaximumLength($max);
        self::assertSame(TextInterface::MAX_LENGTH, $this->facade->getMaximumLength());
    }

    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = 'eTraxis';

        $this->facade->setDefaultValue($value);
        self::assertSame($value, $this->facade->getDefaultValue());
        self::assertNotNull($this->getProperty($parameters, 'defaultValue'));

        $huge = str_pad(null, TextInterface::MAX_LENGTH + 1);
        $trim = str_pad(null, TextInterface::MAX_LENGTH);

        $this->facade->setDefaultValue($huge);
        self::assertSame($trim, $this->facade->getDefaultValue());

        $this->facade->setDefaultValue(null);
        self::assertNull($this->facade->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }

    public function testPCRE()
    {
        self::assertInstanceOf(FieldPCRE::class, $this->facade->getPCRE());
    }
}
