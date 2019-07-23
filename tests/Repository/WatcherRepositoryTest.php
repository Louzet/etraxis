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

use eTraxis\Entity\Issue;
use eTraxis\Entity\User;
use eTraxis\Entity\Watcher;
use eTraxis\Repository\Contracts\WatcherRepositoryInterface;
use eTraxis\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\WatcherRepository
 */
class WatcherRepositoryTest extends WebTestCase
{
    /** @var Contracts\WatcherRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Watcher::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(WatcherRepositoryInterface::class, $this->repository);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionDefault()
    {
        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(26, $collection->to);
        self::assertSame(27, $collection->total);

        $expected = array_map(function (Watcher $watcher) {
            return [$watcher->issue->subject, $watcher->user->email];
        }, $this->repository->findAll());

        $actual = array_map(function (Watcher $watcher) {
            return [$watcher->issue->subject, $watcher->user->email];
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
            'fdooley@example.com',
            'fdooley@example.com',
            'fdooley@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $collection = $this->repository->getCollection(15, WatcherRepositoryInterface::MAX_LIMIT, null, [], [
            User::JSON_EMAIL => WatcherRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(15, $collection->from);
        self::assertSame(26, $collection->to);
        self::assertSame(27, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionLimit()
    {
        $expected = [
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'fdooley@example.com',
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            User::JSON_EMAIL => WatcherRepositoryInterface::SORT_DESC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(27, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
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
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $collection = $this->repository->getCollection(0, WatcherRepositoryInterface::MAX_LIMIT, 'mARq', [], [
            User::JSON_EMAIL => WatcherRepositoryInterface::SORT_DESC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterById()
    {
        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $collection = $this->repository->getCollection(0, WatcherRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_ID => $issue->id,
        ], [
            User::JSON_EMAIL => WatcherRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByEmail()
    {
        $expected = [
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $collection = $this->repository->getCollection(0, WatcherRepositoryInterface::MAX_LIMIT, null, [
            User::JSON_EMAIL => 'mARq',
        ], [
            User::JSON_EMAIL => WatcherRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByFullname()
    {
        $expected = [
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
            'tmarquardt@example.com',
        ];

        $collection = $this->repository->getCollection(0, WatcherRepositoryInterface::MAX_LIMIT, null, [
            User::JSON_FULLNAME => 'rAcY',
        ], [
            User::JSON_EMAIL => WatcherRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByEmail()
    {
        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $collection = $this->repository->getCollection(0, WatcherRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_ID => $issue->id,
        ], [
            User::JSON_EMAIL => WatcherRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByFullname()
    {
        $expected = [
            'fdooley@example.com',
            'tmarquardt@example.com',
        ];

        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $collection = $this->repository->getCollection(0, WatcherRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_ID => $issue->id,
        ], [
            User::JSON_FULLNAME => WatcherRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Watcher $watcher) {
            return $watcher->user->email;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
