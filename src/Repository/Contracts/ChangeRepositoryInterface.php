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
use eTraxis\Entity\Change;
use eTraxis\Entity\Issue;
use eTraxis\Entity\User;

/**
 * Interface to the 'Change' entities repository.
 */
interface ChangeRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Change $entity
     */
    public function persist(Change $entity): void;

    /**
     * Finds all issue changes, visible to specified user.
     *
     * @param Issue $issue
     * @param User  $user
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return Change[]
     */
    public function findAllByIssue(Issue $issue, User $user): array;
}
