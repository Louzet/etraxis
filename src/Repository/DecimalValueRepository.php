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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use eTraxis\Entity\DecimalValue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DecimalValueRepository extends ServiceEntityRepository implements Contracts\DecimalValueRepositoryInterface
{
    use CacheTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DecimalValue::class);

        $this->createCache();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->findInCache($id, function ($id) {
            return parent::find($id);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $value): DecimalValue
    {
        /** @var null|DecimalValue $entity */
        $entity = $this->findOneBy([
            'value' => $value,
        ]);

        // If value doesn't exist yet, create it.
        if ($entity === null) {

            $entity = new DecimalValue($value);

            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush($entity);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function warmup(array $ids): int
    {
        return $this->warmupCache($this, $ids);
    }
}
