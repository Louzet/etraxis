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

use eTraxis\CommandBus\Command\Issues\SuspendIssueCommand;
use eTraxis\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\EventRepository;
use eTraxis\Repository\IssueRepository;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SuspendIssueHandler
{
    protected $security;
    protected $tokens;
    protected $issueRepository;
    protected $eventRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository
    )
    {
        $this->security        = $security;
        $this->tokens          = $tokens;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Command handler.
     *
     * @param SuspendIssueCommand $command
     *
     * @throws \InvalidArgumentException
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(SuspendIssueCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to suspend this issue.');
        }

        $date = date_create_from_format('Y-m-d', $command->date, timezone_open($user->timezone) ?: null);
        $date->setTime(0, 0);

        if ($date->getTimestamp() < time()) {
            throw new \InvalidArgumentException('Date must be in future.');
        }

        $issue->suspend($date->getTimestamp());

        $event = new Event(EventType::ISSUE_SUSPENDED, $issue, $user);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);
    }
}
