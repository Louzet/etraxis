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

namespace eTraxis\CommandBus\Command\Issues;

use eTraxis\Entity\Issue;
use eTraxis\Entity\Watcher;
use eTraxis\TransactionalTestCase;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Issues\WatchIssuesHandler::handle
 */
class WatchIssuesCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\IssueRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var Issue $watching */
        [$watching] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unwatching */
        [$unwatching] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(Watcher::class)->findAll());

        $command = new WatchIssuesCommand([
            'issues' => [
                $watching->id,
                $unwatching->id,
                $forbidden->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ]);

        $this->commandBus->handle($command);

        self::assertCount($count + 1, $this->doctrine->getRepository(Watcher::class)->findAll());
    }
}
