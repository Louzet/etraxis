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

use eTraxis\CommandBus\Command\Users\ResetPasswordCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Command handler.
 */
class ResetPasswordHandler
{
    protected $encoder;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param UserPasswordEncoderInterface $encoder
     * @param UserRepositoryInterface      $repository
     */
    public function __construct(UserPasswordEncoderInterface $encoder, UserRepositoryInterface $repository)
    {
        $this->encoder    = $encoder;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param ResetPasswordCommand $command
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function handle(ResetPasswordCommand $command): void
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->findOneBy([
            'resetToken' => $command->token,
        ]);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        if (!$user->isResetTokenValid($command->token)) {
            throw new NotFoundHttpException();
        }

        try {
            $user->password = $this->encoder->encodePassword($user, $command->password);
        }
        catch (BadCredentialsException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->repository->persist($user);
    }
}
