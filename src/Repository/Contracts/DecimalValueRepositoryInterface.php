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
use eTraxis\Entity\DecimalValue;

/**
 * Interface to the 'DecimalValue' entities repository.
 */
interface DecimalValueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * Finds specified decimal value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value Decimal value.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return DecimalValue
     */
    public function get(string $value): DecimalValue;

    /**
     * Warms up the cache with all entities specified by IDs.
     *
     * @param array $ids
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return int Number of entities pushed to the cache.
     */
    public function warmup(array $ids): int;
}
