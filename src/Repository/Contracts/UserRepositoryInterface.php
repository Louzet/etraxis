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
use eTraxis\Entity\User;
use LazySec\Repository\UserRepositoryInterface as LazySecRepositoryInterface;

/**
 * Interface to the 'User' entities repository.
 */
interface UserRepositoryInterface extends CollectionInterface, LazySecRepositoryInterface, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param User $entity
     */
    public function persist(User $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param User $entity
     */
    public function remove(User $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::refresh()
     *
     * @param User $entity
     */
    public function refresh(User $entity): void;
}
