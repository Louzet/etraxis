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

use eTraxis\Entity\Change;
use eTraxis\Entity\Issue;
use eTraxis\Entity\Project;
use eTraxis\Entity\State;
use eTraxis\Entity\StringValue;
use eTraxis\Entity\Template;
use eTraxis\Entity\User;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\Repository\IssueRepository
 */
class IssueRepositoryTest extends TransactionalTestCase
{
    /** @var Contracts\IssueRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(IssueRepository::class, $this->repository);
    }

    /**
     * @covers ::findByIds
     */
    public function testFindByIds()
    {
        /** @var Issue $issue1 */
        [$issue1] = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);

        /** @var Issue $issue2 */
        [$issue2] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        /** @var Issue $issue3 */
        [$issue3] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $issues = $this->repository->findByIds([$issue1->id, $issue2->id, $issue1->id, $issue3->id]);

        self::assertCount(3, $issues);

        $expected = [$issue1->id, $issue2->id, $issue3->id];

        $actual = array_map(function (Issue $issue) {
            return $issue->id;
        }, $issues);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getTransitionsByUser
     */
    public function testGetTransitionsByUser()
    {
        /** @var Issue $issue4 */
        [/* skipping */, /* skipping */, $issue4] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $issue6 */
        [/* skipping */, /* skipping */, $issue6] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        /** @var User $manager Manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $support Support engineer */
        $support = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var User $author4 A client (the author of the issue 4) */
        $author4 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dtillman@example.com']);

        /** @var User $author6 A client (the author of the issue 6) */
        $author6 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        $states = $this->repository->getTransitionsByUser($issue4, $manager);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue4, $support);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue4, $author4);
        self::assertCount(1, $states);
        self::assertSame('Resolved', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue4, $author6);
        self::assertCount(0, $states);

        $states = $this->repository->getTransitionsByUser($issue6, $manager);
        self::assertCount(1, $states);
        self::assertSame('Opened', $states[0]->name);

        $states = $this->repository->getTransitionsByUser($issue6, $support);
        self::assertCount(1, $states);
        self::assertSame('Opened', $states[0]->name);

        // Author should be able to move the issue to a final state,
        // but the issue has unclosed dependencies.
        $states = $this->repository->getTransitionsByUser($issue6, $author6);
        self::assertCount(0, $states);
    }

    /**
     * @covers ::getResponsiblesByUser
     */
    public function testGetResponsiblesByUser()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $users = $this->repository->getResponsiblesByUser($issue, $manager);
        self::assertCount(4, $users);

        $expected = [
            'Carter Batz',
            'Kailyn Bahringer',
            'Tony Buckridge',
            'Tracy Marquardt',
        ];

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $users);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getResponsiblesByUser
     */
    public function testGetResponsiblesSkipCurrentByUser()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var User $manager */
        $manager = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $users = $this->repository->getResponsiblesByUser($issue, $manager, true);
        self::assertCount(3, $users);

        $expected = [
            'Kailyn Bahringer',
            'Tony Buckridge',
            'Tracy Marquardt',
        ];

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $users);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::changeSubject
     */
    public function testChangeSubject()
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $this->repository->changeSubject($issue, $issue->events[0], 'Development task 1');
        $this->doctrine->getManager()->flush();
        self::assertCount($changes, $this->doctrine->getRepository(Change::class)->findAll());

        $this->repository->changeSubject($issue, $issue->events[0], 'Development task X');
        $this->doctrine->getManager()->flush();
        self::assertSame('Development task X', $issue->subject);
        self::assertCount($changes + 1, $this->doctrine->getRepository(Change::class)->findAll());

        /** @var Change $change */
        [$change] = $this->doctrine->getRepository(Change::class)->findBy([], ['id' => 'DESC']);

        /** @var Contracts\StringValueRepositoryInterface $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        self::assertNull($change->field);
        self::assertSame('Development task 1', $repository->find($change->oldValue)->value);
        self::assertSame('Development task X', $repository->find($change->newValue)->value);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionDefault()
    {
        $this->loginAs('ldoyle@example.com');
        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(41, $collection->to);
        self::assertSame(42, $collection->total);

        $expected = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $this->repository->findAll());

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionByDeveloperB()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionBySupportB()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('vparker@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(17, $collection->to);
        self::assertSame(18, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionByClientB()
    {
        $this->loginAs('aschinner@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionByAuthor()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('lucas.oconnell@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(5, $collection->to);
        self::assertSame(6, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionByResponsible()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Development task 8'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('tmarquardt@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(18, $collection->to);
        self::assertSame(19, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionOffset()
    {
        $expected = [
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Support request 1'],
            ['Molestiae', 'Support request 2'],
            ['Molestiae', 'Support request 3'],
            ['Molestiae', 'Development task 8'],
            ['Molestiae', 'Support request 4'],
            ['Molestiae', 'Support request 5'],
            ['Molestiae', 'Support request 6'],
            ['Excepturi', 'Support request 1'],
            ['Excepturi', 'Support request 2'],
            ['Excepturi', 'Support request 3'],
            ['Excepturi', 'Support request 4'],
            ['Excepturi', 'Support request 5'],
            ['Excepturi', 'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(10, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(10, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionLimit()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, 10, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
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
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, 'pOr', [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(19, $collection->to);
        self::assertSame(20, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterById()
    {
        $this->loginAs('ldoyle@example.com');

        $collection = $this->repository->getCollection(0, 1, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        /** @var Issue $first */
        $first = $collection->data[0];

        $id = (int) mb_substr($first->fullId, mb_strpos($first->fullId, '-') + 1, -1) + 1;

        $expected = range($id * 10, $id * 10 + 9);

        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_ID => '-' . mb_substr('00' . $id, -max(2, mb_strlen($id))),
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(10, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return $issue->id;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterBySubject()
    {
        $expected = [
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_SUBJECT => 'aSk',
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByAuthor()
    {
        $expected = [
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'labshire@example.com']);

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_AUTHOR => $user->id,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByAuthorName()
    {
        $expected = [
            ['Carson Legros', 'Distinctio', 'Support request 2'],
            ['Carson Legros', 'Distinctio', 'Support request 3'],
            ['Carson Legros', 'Distinctio', 'Support request 5'],
            ['Carolyn Hill',  'Molestiae',  'Development task 5'],
            ['Carolyn Hill',  'Molestiae',  'Development task 6'],
            ['Carson Legros', 'Molestiae',  'Support request 2'],
            ['Carson Legros', 'Molestiae',  'Support request 3'],
            ['Carson Legros', 'Molestiae',  'Support request 5'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_AUTHOR_NAME => 'caR',
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->author->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
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
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Support request 1'],
            ['Molestiae', 'Support request 2'],
            ['Molestiae', 'Support request 3'],
            ['Molestiae', 'Development task 8'],
            ['Molestiae', 'Support request 4'],
            ['Molestiae', 'Support request 5'],
            ['Molestiae', 'Support request 6'],
        ];

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Molestiae']);

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_PROJECT => $project->id,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(13, $collection->to);
        self::assertSame(14, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByProjectName()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_PROJECT_NAME => 'Ti',
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(19, $collection->to);
        self::assertSame(20, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByTemplate()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
        ];

        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_TEMPLATE => $template->id,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(5, $collection->to);
        self::assertSame(6, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByTemplateName()
    {
        $expected = [
            ['Molestiae', 'Development task 1'],
            ['Molestiae', 'Development task 2'],
            ['Molestiae', 'Development task 3'],
            ['Molestiae', 'Development task 4'],
            ['Molestiae', 'Development task 5'],
            ['Molestiae', 'Development task 6'],
            ['Molestiae', 'Development task 7'],
            ['Molestiae', 'Development task 8'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_TEMPLATE_NAME => 'vELo',
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByState()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
        ];

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_STATE => $state->id,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByStateName()
    {
        $expected = [
            ['Completed',  'Molestiae',  'Development task 1'],
            ['Completed',  'Molestiae',  'Development task 3'],
            ['Submitted',  'Distinctio', 'Support request 6'],
            ['Duplicated', 'Molestiae',  'Development task 4'],
            ['Duplicated', 'Molestiae',  'Development task 7'],
            ['Submitted',  'Molestiae',  'Support request 6'],
            ['Submitted',  'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_STATE_NAME => 'tED',
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(6, $collection->to);
        self::assertSame(7, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->state->name,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByResponsible()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
        ];

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_RESPONSIBLE => $user->id,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByResponsibleNull()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_RESPONSIBLE => null,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(15, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByResponsibleName()
    {
        $expected = [
            ['Jarrell Kiehn',   'Distinctio', 'Support request 4'],
            ['Tracy Marquardt', 'Distinctio', 'Support request 5'],
            ['Tracy Marquardt', 'Molestiae',  'Support request 4'],
            ['Tracy Marquardt', 'Excepturi',  'Support request 2'],
            ['Carter Batz',     'Excepturi',  'Support request 4'],
            ['Carter Batz',     'Excepturi',  'Support request 5'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_RESPONSIBLE_NAME => 'AR',
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(5, $collection->to);
        self::assertSame(6, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->responsible->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsClonedYes()
    {
        $expected = [
            ['Molestiae', 'Development task 5'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_CLONED => true,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsClonedNo()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_CLONED => false,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(25, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByAge()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_AGE => $issue->age,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(6, $collection->to);
        self::assertSame(7, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsCriticalYes()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_CRITICAL => true,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(11, $collection->to);
        self::assertSame(12, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsCriticalNo()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_CRITICAL => false,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(13, $collection->to);
        self::assertSame(14, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsSuspendedYes()
    {
        $expected = [
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 5'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_SUSPENDED => true,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsSuspendedNo()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_SUSPENDED => false,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(21, $collection->to);
        self::assertSame(22, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsClosedYes()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_CLOSED => true,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(10, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByIsClosedNo()
    {
        $expected = [
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_IS_CLOSED => false,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(15, $collection->to);
        self::assertSame(16, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByDependency()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Development task 8'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [
            Issue::JSON_DEPENDENCY => $issue->id,
        ], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortById()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_ID => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortBySubject()
    {
        $expected = [
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Development task 8'],
            ['Distinctio', 'Support request 1'],
            ['Molestiae',  'Support request 1'],
            ['Excepturi',  'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Molestiae',  'Support request 2'],
            ['Excepturi',  'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Molestiae',  'Support request 4'],
            ['Excepturi',  'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_SUBJECT => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID      => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByCreatedAt()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 6'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_CREATED_AT => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID         => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByChangedAt()
    {
        $expected = [
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 6'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_CHANGED_AT => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID         => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByClosedAt()
    {
        $expected = [
            // opened
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Molestiae',  'Development task 2'],
            ['Distinctio', 'Support request 6'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
            // closed
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 3'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 3'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 3'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_CLOSED_AT => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID        => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByAuthor()
    {
        $expected = [
            ['Ansel Koepp',      'Molestiae',  'Development task 3'],
            ['Carolyn Hill',     'Molestiae',  'Development task 5'],
            ['Carolyn Hill',     'Molestiae',  'Development task 6'],
            ['Carson Legros',    'Distinctio', 'Support request 2'],
            ['Carson Legros',    'Distinctio', 'Support request 3'],
            ['Carson Legros',    'Distinctio', 'Support request 5'],
            ['Carson Legros',    'Molestiae',  'Support request 2'],
            ['Carson Legros',    'Molestiae',  'Support request 3'],
            ['Carson Legros',    'Molestiae',  'Support request 5'],
            ['Derrick Tillman',  'Molestiae',  'Support request 4'],
            ['Derrick Tillman',  'Excepturi',  'Support request 4'],
            ['Dorcas Ernser',    'Molestiae',  'Development task 2'],
            ['Jarrell Kiehn',    'Molestiae',  'Development task 4'],
            ['Jeramy Mueller',   'Distinctio', 'Support request 4'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 2'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 3'],
            ['Jeramy Mueller',   'Excepturi',  'Support request 5'],
            ['Leland Doyle',     'Molestiae',  'Development task 1'],
            ['Lola Abshire',     'Molestiae',  'Development task 7'],
            ['Lola Abshire',     'Molestiae',  'Development task 8'],
            ['Lucas O\'Connell', 'Distinctio', 'Support request 1'],
            ['Lucas O\'Connell', 'Distinctio', 'Support request 6'],
            ['Lucas O\'Connell', 'Molestiae',  'Support request 1'],
            ['Lucas O\'Connell', 'Molestiae',  'Support request 6'],
            ['Lucas O\'Connell', 'Excepturi',  'Support request 1'],
            ['Lucas O\'Connell', 'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_AUTHOR => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID     => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->author->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
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
            ['Distinctio', 'Support request 1'],
            ['Distinctio', 'Support request 2'],
            ['Distinctio', 'Support request 3'],
            ['Distinctio', 'Support request 4'],
            ['Distinctio', 'Support request 5'],
            ['Distinctio', 'Support request 6'],
            ['Excepturi',  'Support request 1'],
            ['Excepturi',  'Support request 2'],
            ['Excepturi',  'Support request 3'],
            ['Excepturi',  'Support request 4'],
            ['Excepturi',  'Support request 5'],
            ['Excepturi',  'Support request 6'],
            ['Molestiae',  'Development task 1'],
            ['Molestiae',  'Development task 2'],
            ['Molestiae',  'Development task 3'],
            ['Molestiae',  'Development task 4'],
            ['Molestiae',  'Development task 5'],
            ['Molestiae',  'Development task 6'],
            ['Molestiae',  'Development task 7'],
            ['Molestiae',  'Support request 1'],
            ['Molestiae',  'Support request 2'],
            ['Molestiae',  'Support request 3'],
            ['Molestiae',  'Development task 8'],
            ['Molestiae',  'Support request 4'],
            ['Molestiae',  'Support request 5'],
            ['Molestiae',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_PROJECT => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID      => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByTemplate()
    {
        $expected = [
            ['Development', 'Molestiae',  'Development task 1'],
            ['Development', 'Molestiae',  'Development task 2'],
            ['Development', 'Molestiae',  'Development task 3'],
            ['Development', 'Molestiae',  'Development task 4'],
            ['Development', 'Molestiae',  'Development task 5'],
            ['Development', 'Molestiae',  'Development task 6'],
            ['Development', 'Molestiae',  'Development task 7'],
            ['Development', 'Molestiae',  'Development task 8'],
            ['Support',     'Distinctio', 'Support request 1'],
            ['Support',     'Distinctio', 'Support request 2'],
            ['Support',     'Distinctio', 'Support request 3'],
            ['Support',     'Distinctio', 'Support request 4'],
            ['Support',     'Distinctio', 'Support request 5'],
            ['Support',     'Distinctio', 'Support request 6'],
            ['Support',     'Molestiae',  'Support request 1'],
            ['Support',     'Molestiae',  'Support request 2'],
            ['Support',     'Molestiae',  'Support request 3'],
            ['Support',     'Molestiae',  'Support request 4'],
            ['Support',     'Molestiae',  'Support request 5'],
            ['Support',     'Molestiae',  'Support request 6'],
            ['Support',     'Excepturi',  'Support request 1'],
            ['Support',     'Excepturi',  'Support request 2'],
            ['Support',     'Excepturi',  'Support request 3'],
            ['Support',     'Excepturi',  'Support request 4'],
            ['Support',     'Excepturi',  'Support request 5'],
            ['Support',     'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_TEMPLATE => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID       => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->state->template->name,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByState()
    {
        $expected = [
            ['Assigned',   'Molestiae',  'Development task 2'],
            ['Assigned',   'Molestiae',  'Development task 8'],
            ['Completed',  'Molestiae',  'Development task 1'],
            ['Completed',  'Molestiae',  'Development task 3'],
            ['Duplicated', 'Molestiae',  'Development task 4'],
            ['Duplicated', 'Molestiae',  'Development task 7'],
            ['New',        'Molestiae',  'Development task 5'],
            ['New',        'Molestiae',  'Development task 6'],
            ['Opened',     'Distinctio', 'Support request 2'],
            ['Opened',     'Distinctio', 'Support request 4'],
            ['Opened',     'Distinctio', 'Support request 5'],
            ['Opened',     'Molestiae',  'Support request 2'],
            ['Opened',     'Molestiae',  'Support request 4'],
            ['Opened',     'Molestiae',  'Support request 5'],
            ['Opened',     'Excepturi',  'Support request 2'],
            ['Opened',     'Excepturi',  'Support request 4'],
            ['Opened',     'Excepturi',  'Support request 5'],
            ['Resolved',   'Distinctio', 'Support request 1'],
            ['Resolved',   'Distinctio', 'Support request 3'],
            ['Resolved',   'Molestiae',  'Support request 1'],
            ['Resolved',   'Molestiae',  'Support request 3'],
            ['Resolved',   'Excepturi',  'Support request 1'],
            ['Resolved',   'Excepturi',  'Support request 3'],
            ['Submitted',  'Distinctio', 'Support request 6'],
            ['Submitted',  'Molestiae',  'Support request 6'],
            ['Submitted',  'Excepturi',  'Support request 6'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_STATE => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID    => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->state->name,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByResponsible()
    {
        $expected = [
            [null,               'Distinctio', 'Support request 1'],
            [null,               'Distinctio', 'Support request 3'],
            [null,               'Molestiae',  'Development task 1'],
            [null,               'Molestiae',  'Development task 3'],
            [null,               'Distinctio', 'Support request 6'],
            [null,               'Molestiae',  'Development task 4'],
            [null,               'Molestiae',  'Development task 5'],
            [null,               'Molestiae',  'Development task 6'],
            [null,               'Molestiae',  'Development task 7'],
            [null,               'Molestiae',  'Support request 1'],
            [null,               'Molestiae',  'Support request 3'],
            [null,               'Molestiae',  'Support request 6'],
            [null,               'Excepturi',  'Support request 1'],
            [null,               'Excepturi',  'Support request 3'],
            [null,               'Excepturi',  'Support request 6'],
            ['Ansel Koepp',      'Molestiae',  'Development task 2'],
            ['Carter Batz',      'Excepturi',  'Support request 4'],
            ['Carter Batz',      'Excepturi',  'Support request 5'],
            ['Jarrell Kiehn',    'Distinctio', 'Support request 4'],
            ['Kailyn Bahringer', 'Molestiae',  'Support request 5'],
            ['Nikko Hills',      'Distinctio', 'Support request 2'],
            ['Nikko Hills',      'Molestiae',  'Support request 2'],
            ['Nikko Hills',      'Molestiae',  'Development task 8'],
            ['Tracy Marquardt',  'Distinctio', 'Support request 5'],
            ['Tracy Marquardt',  'Molestiae',  'Support request 4'],
            ['Tracy Marquardt',  'Excepturi',  'Support request 2'],
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_RESPONSIBLE => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID          => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [
                $issue->responsible === null ? null : $issue->responsible->fullname,
                $issue->state->template->project->name,
                $issue->subject,
            ];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByAge()
    {
        $expected = [
            ['Molestiae',  'Development task 4'],   //    1 day
            ['Distinctio', 'Support request 1'],    //    2 days
            ['Distinctio', 'Support request 3'],    //    2 days
            ['Molestiae',  'Development task 7'],   //    2 days
            ['Molestiae',  'Support request 1'],    //    2 days
            ['Molestiae',  'Support request 3'],    //    2 days
            ['Excepturi',  'Support request 1'],    //    2 days
            ['Excepturi',  'Support request 3'],    //    2 days
            ['Molestiae',  'Development task 1'],   //    3 days
            ['Molestiae',  'Development task 3'],   //    5 days
            ['Excepturi',  'Support request 6'],    //  345 days
            ['Excepturi',  'Support request 5'],    //  348 days
            ['Excepturi',  'Support request 4'],    //  366 days
            ['Excepturi',  'Support request 2'],    //  410 days
            ['Molestiae',  'Support request 5'],    //  482 days
            ['Molestiae',  'Support request 4'],    //  494 days
            ['Molestiae',  'Support request 6'],    //  512 days
            ['Molestiae',  'Development task 8'],   //  518 days
            ['Molestiae',  'Support request 2'],    //  553 days
            ['Molestiae',  'Development task 6'],   //  606 days
            ['Molestiae',  'Development task 5'],   //  661 days
            ['Distinctio', 'Support request 6'],    //  693 days
            ['Molestiae',  'Development task 2'],   //  725 days
            ['Distinctio', 'Support request 5'],    //  933 days
            ['Distinctio', 'Support request 4'],    //  946 days
            ['Distinctio', 'Support request 2'],    // 1057 days
        ];

        $this->loginAs('amarvin@example.com');
        $collection = $this->repository->getCollection(0, IssueRepositoryInterface::MAX_LIMIT, null, [], [
            Issue::JSON_AGE => IssueRepositoryInterface::SORT_ASC,
            Issue::JSON_ID  => IssueRepositoryInterface::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(25, $collection->to);
        self::assertSame(26, $collection->total);

        $actual = array_map(function (Issue $issue) {
            return [$issue->state->template->project->name, $issue->subject];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
