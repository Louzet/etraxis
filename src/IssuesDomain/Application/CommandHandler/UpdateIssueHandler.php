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

namespace eTraxis\IssuesDomain\Application\CommandHandler;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\IssuesDomain\Application\Command\UpdateIssueCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\FieldValueRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateIssueHandler
{
    protected $translator;
    protected $security;
    protected $validator;
    protected $tokens;
    protected $issueRepository;
    protected $eventRepository;
    protected $valueRepository;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param TranslatorInterface           $translator
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param FieldValueRepository          $valueRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        TranslatorInterface           $translator,
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        FieldValueRepository          $valueRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->translator      = $translator;
        $this->security        = $security;
        $this->validator       = $validator;
        $this->tokens          = $tokens;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
        $this->valueRepository = $valueRepository;
        $this->manager         = $manager;
    }

    /**
     * Command handler.
     *
     * @param UpdateIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(UpdateIssueCommand $command): void
    {
        /** @var null|\eTraxis\IssuesDomain\Model\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this issue.');
        }

        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);

        $this->eventRepository->persist($event);

        if (mb_strlen($command->subject) !== 0) {
            $this->issueRepository->changeSubject($issue, $event, $command->subject);
        }

        // Validate field values.
        $defaults    = [];
        $constraints = [];

        foreach ($issue->values as $fieldValue) {
            $field = $fieldValue->field;

            $defaults[$field->id]    = $this->valueRepository->getFieldValue($fieldValue, $user);
            $constraints[$field->id] = $field->getFacade($this->manager)->getValidationConstraints($this->translator, $fieldValue->createdAt);
        }

        $command->fields = $command->fields + $defaults;

        /** @var \Symfony\Component\Validator\Mapping\ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor(UpdateIssueCommand::class);

        $metadata->addPropertyConstraint('fields', new Assert\Collection([
            'fields'             => $constraints,
            'allowExtraFields'   => false,
            'allowMissingFields' => false,
        ]));

        $errors = $this->validator->validate($command);

        if (count($errors)) {
            throw InvalidCommandException::onCommand($command, $errors);
        }

        // Set field values.
        foreach ($issue->values as $fieldValue) {
            $this->valueRepository->setFieldValue($issue, $event, $fieldValue->field, $command->fields[$fieldValue->field->id]);
        }
    }
}
