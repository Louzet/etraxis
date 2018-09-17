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

namespace eTraxis\TemplatesDomain\Model\Entity;

use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class StateRoleTransitionTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 3);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 4);

        $transition = new StateRoleTransition($from, $to, SystemRole::AUTHOR);
        self::assertSame($from, $this->getProperty($transition, 'fromState'));
        self::assertSame($to, $this->getProperty($transition, 'toState'));
        self::assertSame(SystemRole::AUTHOR, $this->getProperty($transition, 'role'));
    }

    public function testConstructorExceptionStates()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('States must belong the same template');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template1 = new Template($project);
        $this->setProperty($template1, 'id', 2);

        $template2 = new Template($project);
        $this->setProperty($template2, 'id', 3);

        $from = new State($template1, StateType::INITIAL);
        $this->setProperty($from, 'id', 4);

        $to = new State($template2, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 5);

        new StateRoleTransition($from, $to, 'foo');
    }

    public function testConstructorExceptionRole()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown system role: foo');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 3);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 4);

        new StateRoleTransition($from, $to, 'foo');
    }

    public function testJsonSerialize()
    {
        $expected = [
            'state' => 4,
            'role'  => 'author',
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 3);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 4);

        $transition = new StateRoleTransition($from, $to, SystemRole::AUTHOR);

        self::assertSame($expected, $transition->jsonSerialize());
    }
}
