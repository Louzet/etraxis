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
use eTraxis\Entity\StringValue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StringValueRepository extends ServiceEntityRepository implements Contracts\StringValueRepositoryInterface
{
    use CacheTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StringValue::class);

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
    public function get(string $value): StringValue
    {
        /** @var null|StringValue $entity */
        $entity = $this->findOneBy([
            'token' => md5($value),
        ]);

        // If value doesn't exist yet, create it.
        if ($entity === null) {

            $entity = new StringValue($value);

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
