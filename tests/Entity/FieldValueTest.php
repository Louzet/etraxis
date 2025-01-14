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

namespace eTraxis\Entity;

use eTraxis\Dictionary\FieldType;
use eTraxis\Dictionary\StateType;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldValue
 */
class FieldValueTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $template = new Template(new Project());
        $this->setProperty($template, 'id', 1);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $user = new User();
        $this->setProperty($user, 'id', 4);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 5);
        $issue->state = $initial;

        $field = new Field($state, FieldType::LIST);
        $this->setProperty($field, 'id', 6);
        $field->name = 'foo';

        $value = new FieldValue($issue, $field, 100);

        self::assertSame($issue, $value->issue);
        self::assertSame($field, $value->field);
        self::assertSame(100, $value->value);
        self::assertLessThanOrEqual(2, time() - $value->createdAt);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown field: foo');

        $template = new Template(new Project());
        $this->setProperty($template, 'id', 1);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 2);

        $template2 = new Template(new Project());
        $this->setProperty($template2, 'id', 3);

        $state = new State($template2, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 4);

        $user = new User();
        $this->setProperty($user, 'id', 5);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 6);
        $issue->state = $initial;

        $field = new Field($state, FieldType::LIST);
        $this->setProperty($field, 'id', 7);
        $field->name = 'foo';

        $value = new FieldValue($issue, $field, 100);

        self::assertSame($issue, $value->issue);
        self::assertSame($field, $value->field);
        self::assertSame(100, $value->value);
    }
}
