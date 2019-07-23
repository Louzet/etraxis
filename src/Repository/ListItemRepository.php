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
use eTraxis\Entity\Field;
use eTraxis\Entity\ListItem;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ListItemRepository extends ServiceEntityRepository implements Contracts\ListItemRepositoryInterface
{
    use CacheTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ListItem::class);

        $this->createCache();
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(ListItem $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->deleteFromCache($entity->id);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(ListItem $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->deleteFromCache($entity->id);
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
    public function findAllByField(Field $field): array
    {
        return $this->findBy([
            'field' => $field,
        ], [
            'value' => 'ASC',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByValue(Field $field, int $value): ?ListItem
    {
        /** @var ListItem $entity */
        $entity = $this->findOneBy([
            'field' => $field,
            'value' => $value,
        ]);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByText(Field $field, string $text): ?ListItem
    {
        /** @var ListItem $entity */
        $entity = $this->findOneBy([
            'field' => $field,
            'text'  => $text,
        ]);

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
