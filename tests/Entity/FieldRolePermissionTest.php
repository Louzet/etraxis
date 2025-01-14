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

use eTraxis\Dictionary\FieldPermission;
use eTraxis\Dictionary\FieldType;
use eTraxis\Dictionary\StateType;
use eTraxis\Dictionary\SystemRole;
use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\FieldRolePermission
 */
class FieldRolePermissionTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        $permission = new FieldRolePermission($field, SystemRole::AUTHOR, FieldPermission::READ_WRITE);
        self::assertSame($field, $this->getProperty($permission, 'field'));
        self::assertSame(SystemRole::AUTHOR, $this->getProperty($permission, 'role'));
        self::assertSame(FieldPermission::READ_WRITE, $this->getProperty($permission, 'permission'));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionRole()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown system role: foo');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        new FieldRolePermission($field, 'foo', FieldPermission::READ_WRITE);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorExceptionPermission()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown permission: bar');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        new FieldRolePermission($field, SystemRole::AUTHOR, 'bar');
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'role'       => 'author',
            'permission' => 'RW',
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        $permission = new FieldRolePermission($field, SystemRole::AUTHOR, FieldPermission::READ_WRITE);

        self::assertSame($expected, $permission->jsonSerialize());
    }
}
