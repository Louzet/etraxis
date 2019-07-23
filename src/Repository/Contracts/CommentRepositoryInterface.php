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
use eTraxis\Entity\Comment;
use eTraxis\Entity\Issue;

/**
 * Interface to the 'Comment' entities repository.
 */
interface CommentRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Comment $entity
     */
    public function persist(Comment $entity): void;

    /**
     * Finds all comments of specified issue.
     *
     * @param Issue $issue
     * @param bool  $showPrivate
     *
     * @return Comment[]
     */
    public function findAllByIssue(Issue $issue, bool $showPrivate): array;
}
