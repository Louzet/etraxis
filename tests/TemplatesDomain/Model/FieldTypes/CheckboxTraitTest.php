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
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\WebTestCase;

class CheckboxTraitTest extends WebTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
    protected $validator;

    /** @var Field */
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testValidationConstraints()
    {
        $value = false;
        self::assertCount(0, $this->validator->validate($value, $this->object->asCheckbox()->getValidationConstraints($this->translator)));

        $value = true;
        self::assertCount(0, $this->validator->validate($value, $this->object->asCheckbox()->getValidationConstraints($this->translator)));
    }

    public function testDefaultValue()
    {
        $field      = $this->object->asCheckbox();
        $parameters = $this->getProperty($this->object, 'parameters');

        $field->setDefaultValue(true);
        self::assertTrue($field->getDefaultValue());
        self::assertSame(1, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue(false);
        self::assertFalse($field->getDefaultValue());
        self::assertSame(0, $this->getProperty($parameters, 'defaultValue'));
    }
}