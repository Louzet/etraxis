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
use eTraxis\Entity\Template;
use eTraxis\Entity\User;

/**
 * Interface to the 'Template' entities repository.
 */
interface TemplateRepositoryInterface extends CollectionInterface, ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     *
     * @param Template $entity
     */
    public function persist(Template $entity): void;

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::remove()
     *
     * @param Template $entity
     */
    public function remove(Template $entity): void;

    /**
     * Returns list of templates which can be used by specified user to create new issues.
     *
     * @param User $user
     *
     * @return Template[]
     */
    public function getTemplatesByUser(User $user): array;
}
