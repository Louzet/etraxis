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

namespace eTraxis\Voter;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "User" entities.
 */
class UserVoter extends Voter
{
    use VoterTrait;

    public const CREATE_USER  = 'user.create';
    public const UPDATE_USER  = 'user.update';
    public const DELETE_USER  = 'user.delete';
    public const DISABLE_USER = 'user.disable';
    public const ENABLE_USER  = 'user.enable';
    public const UNLOCK_USER  = 'user.unlock';
    public const SET_PASSWORD = 'user.password';

    protected $attributes = [
        self::CREATE_USER  => null,
        self::UPDATE_USER  => User::class,
        self::DELETE_USER  => User::class,
        self::DISABLE_USER => User::class,
        self::ENABLE_USER  => User::class,
        self::UNLOCK_USER  => User::class,
        self::SET_PASSWORD => User::class,
    ];

    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // User must be logged in.
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {

            case self::CREATE_USER:
                return $this->isCreateGranted($user);

            case self::UPDATE_USER:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_USER:
                return $this->isDeleteGranted($subject, $user);

            case self::DISABLE_USER:
                return $this->isDisableGranted($subject, $user);

            case self::ENABLE_USER:
                return $this->isEnableGranted($subject, $user);

            case self::UNLOCK_USER:
                return $this->isUnlockGranted($subject, $user);

            case self::SET_PASSWORD:
                return $this->isSetPasswordGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether the current user can create a new one.
     *
     * @param User $user Current user.
     *
     * @return bool
     */
    protected function isCreateGranted(User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be updated.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(User $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be deleted.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    protected function isDeleteGranted(User $subject, User $user): bool
    {
        // User must be an admin and cannot delete oneself.
        if (!$user->isAdmin || $subject->id === $user->id) {
            return false;
        }

        // Can't delete a user if mentioned in an issue history.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(event.id)')
            ->from(Event::class, 'event')
            ->where('event.user = :user')
            ->orWhere($query->expr()->andX(
                'event.type = :type',
                'event.parameter = :user'
            ))
            ->setParameter('user', $subject->id)
            ->setParameter('type', EventType::ISSUE_ASSIGNED);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return $result === 0;
    }

    /**
     * Whether the specified user can be disabled.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isDisableGranted(User $subject, User $user): bool
    {
        // Can't disable oneself.
        if ($subject->id === $user->id) {
            return false;
        }

        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be enabled.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isEnableGranted(User $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be unlocked.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isUnlockGranted(User $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether a password of the specified user can be set.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isSetPasswordGranted(User $subject, User $user): bool
    {
        // Can't set password of an external account.
        if ($subject->isAccountExternal()) {
            return false;
        }

        return $user->isAdmin || $subject->id === $user->id;
    }
}
