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
use eTraxis\Entity\Comment;
use eTraxis\Entity\Issue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CommentRepository extends ServiceEntityRepository implements Contracts\CommentRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(Comment $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByIssue(Issue $issue, bool $showPrivate): array
    {
        $query = $this->createQueryBuilder('comment')
            ->innerJoin('comment.event', 'event')
            ->addSelect('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->setParameter('issue', $issue);

        if (!$showPrivate) {
            $query->andWhere('comment.isPrivate = :private');
            $query->setParameter('private', false);
        }

        return $query->getQuery()->getResult();
    }
}
