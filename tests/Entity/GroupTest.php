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

use eTraxis\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\Entity\Group
 */
class GroupTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $group = new Group($project);
        self::assertSame($project, $this->getProperty($group, 'project'));

        $group = new Group();
        self::assertNull($this->getProperty($group, 'project'));
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerializeLocalGroup()
    {
        $expected = [
            'id'          => 2,
            'project'     => [
                'id'          => 1,
                'name'        => 'Project',
                'description' => 'Test project',
                'created'     => time(),
                'suspended'   => false,
            ],
            'name'        => 'Team',
            'description' => 'Project developers',
            'global'      => false,
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $project->name        = 'Project';
        $project->description = 'Test project';

        $group = new Group($project);
        $this->setProperty($group, 'id', 2);

        $group->name        = 'Team';
        $group->description = 'Project developers';

        self::assertSame($expected, $group->jsonSerialize());
    }

    /**
     * @covers ::addMember
     * @covers ::getters
     * @covers ::removeMember
     */
    public function testMembers()
    {
        $group = new Group(new Project());
        self::assertSame([], $group->members);

        $user1 = new User();
        $user2 = new User();

        $this->setProperty($user1, 'id', 1);
        $this->setProperty($user2, 'id', 2);

        $group->addMember($user1);
        $group->addMember($user2);

        self::assertSame([$user1, $user2], $group->members);

        $group->removeMember($user1);

        self::assertSame([$user2], $group->members);
    }

    /**
     * @covers ::getters
     */
    public function testIsGlobal()
    {
        $group = new Group(new Project());
        self::assertFalse($group->isGlobal);

        $group = new Group();
        self::assertTrue($group->isGlobal);
    }
}
