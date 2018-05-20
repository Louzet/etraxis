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

namespace eTraxis\TemplatesDomain\Model\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ListItemRepository extends ServiceEntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ListItem::class);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(ListItem $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ListItem $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * Finds list item by value.
     *
     * @param Field $field
     * @param int   $value
     *
     * @return null|ListItem
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
     * Finds list item by text.
     *
     * @param Field  $field
     * @param string $text
     *
     * @return null|ListItem
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
}
