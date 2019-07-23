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

use eTraxis\CommandBus\Command\Users\ForgetPasswordCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Service\Contracts\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
class ForgetPasswordHandler
{
    protected $translator;
    protected $mailer;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TranslatorInterface     $translator
     * @param MailerInterface         $mailer
     * @param UserRepositoryInterface $repository
     */
    public function __construct(
        TranslatorInterface     $translator,
        MailerInterface         $mailer,
        UserRepositoryInterface $repository
    )
    {
        $this->translator = $translator;
        $this->mailer     = $mailer;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param ForgetPasswordCommand $command
     *
     * @throws \Exception
     *
     * @return null|string Generated reset token (NULL if user not found).
     */
    public function handle(ForgetPasswordCommand $command): ?string
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->findOneByUsername($command->email);

        if ($user === null || $user->isAccountExternal()) {
            return null;
        }

        // Token expires in 2 hours.
        $token = $user->generateResetToken(new \DateInterval('PT2H'));
        $this->repository->persist($user);

        $this->mailer->send(
            $user->email,
            $user->fullname,
            $this->translator->trans('email.forgot_password.subject', [], null, $user->locale),
            'security/forgot/email.html.twig',
            [
                'locale' => $user->locale,
                'token'  => $token,
            ]
        );

        return $token;
    }
}
