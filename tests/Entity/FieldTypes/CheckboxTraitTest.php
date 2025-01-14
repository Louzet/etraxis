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

namespace eTraxis\Entity\FieldTypes;

use eTraxis\Dictionary\FieldType;
use eTraxis\Dictionary\StateType;
use eTraxis\Entity\Field;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\Template;
use eTraxis\ReflectionTrait;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldTypes\CheckboxTrait
 */
class CheckboxTraitTest extends WebTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Contracts\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
    protected $validator;

    /** @var Field */
    protected $object;

    /** @var CheckboxInterface */
    protected $facade;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($this->object, 'id', 1);

        $this->facade = $this->callMethod($this->object, 'getFacade', [$this->doctrine->getManager()]);
    }

    /**
     * @covers ::asCheckbox
     */
    public function testJsonSerialize()
    {
        $expected = [
            'default' => false,
        ];

        self::assertSame($expected, $this->facade->jsonSerialize());
    }

    /**
     * @covers ::asCheckbox
     */
    public function testValidationConstraints()
    {
        $value = false;
        self::assertCount(0, $this->validator->validate($value, $this->facade->getValidationConstraints($this->translator)));

        $value = true;
        self::assertCount(0, $this->validator->validate($value, $this->facade->getValidationConstraints($this->translator)));
    }

    /**
     * @covers ::asCheckbox
     */
    public function testDefaultValue()
    {
        $parameters = $this->getProperty($this->object, 'parameters');

        $this->facade->setDefaultValue(true);
        self::assertTrue($this->facade->getDefaultValue());
        self::assertSame(1, $this->getProperty($parameters, 'defaultValue'));

        $this->facade->setDefaultValue(false);
        self::assertFalse($this->facade->getDefaultValue());
        self::assertSame(0, $this->getProperty($parameters, 'defaultValue'));
    }
}
