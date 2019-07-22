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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\CommandBus\Command\Issues\ChangeStateCommand;
use eTraxis\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\EventRepository;
use eTraxis\Repository\FieldValueRepository;
use eTraxis\Repository\IssueRepository;
use eTraxis\Repository\StateRepository;
use eTraxis\Repository\UserRepository;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
class ChangeStateHandler extends AbstractIssueHandler
{
    protected $stateRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TranslatorInterface           $translator
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $userRepository
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param FieldValueRepository          $valueRepository
     * @param EntityManagerInterface        $manager
     * @param StateRepository               $stateRepository
     */
    public function __construct(
        TranslatorInterface           $translator,
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        UserRepository                $userRepository,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        FieldValueRepository          $valueRepository,
        EntityManagerInterface        $manager,
        StateRepository               $stateRepository
    )
    {
        parent::__construct($translator, $security, $validator, $tokens, $userRepository, $issueRepository, $eventRepository, $valueRepository, $manager);

        $this->stateRepository = $stateRepository;
    }

    /**
     * Command handler.
     *
     * @param ChangeStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(ChangeStateCommand $command): void
    {
        /** @var null|\eTraxis\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        /** @var null|\eTraxis\Entity\State $state */
        $state = $this->stateRepository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(IssueVoter::CHANGE_STATE, [$issue, $state])) {
            throw new AccessDeniedHttpException('You are not allowed to change the state.');
        }

        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        if (!$issue->isClosed && $state->isFinal) {
            $eventType = EventType::ISSUE_CLOSED;
        }
        elseif ($issue->isClosed && !$state->isFinal) {
            $eventType = EventType::ISSUE_REOPENED;
        }
        else {
            $eventType = EventType::STATE_CHANGED;
        }

        $issue->state = $state;

        $event = new Event($eventType, $issue, $user, $state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        $this->validateState($issue, $event, $command);
    }
}
