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

namespace eTraxis\Repository;

use eTraxis\Entity\Group;
use eTraxis\Entity\Project;
use eTraxis\Repository\Contracts\GroupRepositoryInterface;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\GroupRepository
 */
class GroupRepositoryTest extends WebTestCase
{
    /** @var Contracts\GroupRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(GroupRepository::class, $this->repository);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionDefault()
    {
        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $expected = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $this->repository->findAll());

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionOffset()
    {
        $expected = [
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $collection = $this->repository->getCollection(10, GroupRepositoryInterface::MAX_LIMIT, null, [], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(10, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionLimit()
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::querySearch
     */
    public function testGetCollectionSearch()
    {
        $expected = [
            ['Clients',         'Clients A'],
            ['Clients',         'Clients B'],
            ['Clients',         'Clients C'],
            ['Clients',         'Clients D'],
            ['Company Clients', null],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, 'cliENTs', [], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(5, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByProject()
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
        ];

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [
            Group::JSON_PROJECT => $project->id,
        ], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByProjectNull()
    {
        $expected = [
            ['Company Clients', null],
            ['Company Staff',   null],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [
            Group::JSON_PROJECT => null,
        ], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByName()
    {
        $expected = [
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [
            Group::JSON_NAME => 'eRS',
        ], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(11, $collection->to);
        self::assertSame(12, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByNameNull()
    {
        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [
            Group::JSON_NAME => null,
        ], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByDescription()
    {
        $expected = [
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [
            Group::JSON_DESCRIPTION => 'eRS a',
        ], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByDescriptionNull()
    {
        $expected = [
            ['Company Clients', null],
            ['Company Staff',   null],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [
            Group::JSON_DESCRIPTION => null,
        ], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByGlobal()
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [
            Group::JSON_GLOBAL => false,
        ], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(15, $collection->to);
        self::assertSame(16, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByProject()
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Developers',        'Developers A'],
            ['Managers',          'Managers A'],
            ['Support Engineers', 'Support Engineers A'],
            ['Clients',           'Clients C'],
            ['Developers',        'Developers C'],
            ['Managers',          'Managers C'],
            ['Support Engineers', 'Support Engineers C'],
            ['Clients',           'Clients B'],
            ['Developers',        'Developers B'],
            ['Managers',          'Managers B'],
            ['Support Engineers', 'Support Engineers B'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [], [
            Group::JSON_PROJECT     => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByName()
    {
        $expected = [
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [], [
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByDescription()
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [], [
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByGlobal()
    {
        $expected = [
            ['Company Clients',   null],
            ['Company Staff',     null],
            ['Clients',           'Clients A'],
            ['Clients',           'Clients B'],
            ['Clients',           'Clients C'],
            ['Clients',           'Clients D'],
            ['Developers',        'Developers A'],
            ['Developers',        'Developers B'],
            ['Developers',        'Developers C'],
            ['Developers',        'Developers D'],
            ['Managers',          'Managers A'],
            ['Managers',          'Managers B'],
            ['Managers',          'Managers C'],
            ['Managers',          'Managers D'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
            ['Support Engineers', 'Support Engineers D'],
        ];

        $collection = $this->repository->getCollection(0, GroupRepositoryInterface::MAX_LIMIT, null, [], [
            Group::JSON_GLOBAL      => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_NAME        => GroupRepositoryInterface::SORT_ASC,
            Group::JSON_DESCRIPTION => GroupRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
