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

namespace eTraxis\CommandBus\CommandHandler\Issues;

use eTraxis\CommandBus\Command\Issues\ReassignIssueCommand;
use eTraxis\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\EventRepository;
use eTraxis\Repository\IssueRepository;
use eTraxis\Repository\UserRepository;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class ReassignIssueHandler
{
    protected $security;
    protected $tokens;
    protected $userRepository;
    protected $issueRepository;
    protected $eventRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $userRepository
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        UserRepository                $userRepository,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository
    )
    {
        $this->security        = $security;
        $this->tokens          = $tokens;
        $this->userRepository  = $userRepository;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Command handler.
     *
     * @param ReassignIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(ReassignIssueCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        $responsible = $this->userRepository->find($command->responsible);

        if (!$responsible) {
            throw new NotFoundHttpException('Unknown user.');
        }

        if (!$this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$issue, $responsible])) {
            throw new AccessDeniedHttpException('You are not allowed to reassign this issue.');
        }

        if ($issue->responsible !== $responsible) {

            $issue->responsible = $responsible;

            $event = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $responsible->id);

            $this->issueRepository->persist($issue);
            $this->eventRepository->persist($event);
        }
    }
}
