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

namespace eTraxis\CommandBus\CommandHandler\Users;

use eTraxis\CommandBus\Command\Users\UpdateProfileCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateProfileHandler
{
    protected $validator;
    protected $tokens;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param ValidatorInterface      $validator
     * @param TokenStorageInterface   $tokens
     * @param UserRepositoryInterface $repository
     */
    public function __construct(
        ValidatorInterface      $validator,
        TokenStorageInterface   $tokens,
        UserRepositoryInterface $repository
    )
    {
        $this->validator  = $validator;
        $this->tokens     = $tokens;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateProfileCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    public function handle(UpdateProfileCommand $command): void
    {
        $token = $this->tokens->getToken();

        // User must be logged in.
        if (!$token) {
            throw new AccessDeniedHttpException();
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $token->getUser();

        if ($user->isAccountExternal()) {
            throw new AccessDeniedHttpException();
        }

        $user->email    = $command->email;
        $user->fullname = $command->fullname;

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            // Emails are used as usernames, so restore the entity to avoid impersonation.
            $this->repository->refresh($user);

            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($user);
    }
}
