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
 * Interface to the 'Event' entities repository.
 */
interface EventRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Event $entity
     */
    public function persist(Event $entity): void;

    /**
     * Finds all events of specified issue.
     *
     * @param Issue $issue
     * @param bool  $showPrivate
     *
     * @return Event[]
     */
    public function findAllByIssue(Issue $issue, bool $showPrivate): array;
}
