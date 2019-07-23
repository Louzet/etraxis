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

namespace eTraxis\Repository\Contracts;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Persistence\ObjectRepository;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;

/**
 * Interface to the 'Issue' entities repository.
 */
interface IssueRepositoryInterface extends CollectionInterface, ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Issue $entity
     */
    public function persist(Issue $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param Issue $entity
     */
    public function remove(Issue $entity): void;

    /**
     * Finds issues specified by their ID.
     *
     * @param int[] $ids List of IDs.
     *
     * @return Issue[]
     */
    public function findByIds(array $ids): array;

    /**
     * Sets new subject of the specified issue.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param Issue  $issue   Issie whose subject is being set.
     * @param Event  $event   Event related to this change.
     * @param string $subject New subject.
     */
    public function changeSubject(Issue $issue, Event $event, string $subject): void;
}
