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

namespace eTraxis\TemplatesDomain\Application\Voter;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "State" entities.
 */
class StateVoter extends Voter
{
    public const CREATE_STATE = 'state.create';
    public const UPDATE_STATE = 'state.update';
    public const DELETE_STATE = 'state.delete';
    public const SET_INITIAL  = 'state.set_initial';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = [
            self::CREATE_STATE => Template::class,
            self::UPDATE_STATE => State::class,
            self::DELETE_STATE => State::class,
            self::SET_INITIAL  => State::class,
        ];

        // Whether the attribute is supported.
        if (!array_key_exists($attribute, $attributes)) {
            return false;
        }

        // The subject must be an object of expected class.
        return is_object($subject) && get_class($subject) === $attributes[$attribute];
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // User must be logged in.
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {

            case self::CREATE_STATE:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_STATE:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_STATE:
                return $this->isDeleteGranted($subject, $user);

            case self::SET_INITIAL:
                return $this->isSetInitialGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new state can be created in the specified template.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isCreateGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin && $subject->isLocked;
    }

    /**
     * Whether the specified state can be updated.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked;
    }

    /**
     * Whether the specified state can be deleted.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isDeleteGranted(State $subject, User $user): bool
    {
        /** @todo Can't delete state if it was used in at least one issue. */

        return $user->isAdmin && $subject->template->isLocked;
    }

    /**
     * Whether the specified state can be set as initial one.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isSetInitialGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked;
    }
}
