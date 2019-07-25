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

namespace eTraxis\Controller\API;

use eTraxis\CommandBus\Command\Issues as Command;
use eTraxis\Dictionary\FieldType;
use eTraxis\Entity\Change;
use eTraxis\Entity\Event;
use eTraxis\Entity\Issue;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\Repository\CollectionTrait;
use eTraxis\Repository\Contracts\ChangeRepositoryInterface;
use eTraxis\Repository\Contracts\CommentRepositoryInterface;
use eTraxis\Repository\Contracts\DecimalValueRepositoryInterface;
use eTraxis\Repository\Contracts\EventRepositoryInterface;
use eTraxis\Repository\Contracts\FileRepositoryInterface;
use eTraxis\Repository\Contracts\IssueRepositoryInterface;
use eTraxis\Repository\Contracts\LastReadRepositoryInterface;
use eTraxis\Repository\Contracts\ListItemRepositoryInterface;
use eTraxis\Repository\Contracts\StateRepositoryInterface;
use eTraxis\Repository\Contracts\StringValueRepositoryInterface;
use eTraxis\Repository\Contracts\TextValueRepositoryInterface;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use eTraxis\Repository\Contracts\WatcherRepositoryInterface;
use eTraxis\Voter\IssueVoter;
use League\Tactician\CommandBus;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as API;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * API controller for '/issues' resource.
 *
 * @Route("/api/issues")
 * @Security("is_granted('ROLE_USER')")
 *
 * @API\Tag(name="Issues")
 */
class ApiIssuesController extends AbstractController
{
    use CollectionTrait;

    /**
     * Returns list of issues.
     *
     * @Route("", name="api_issues_list", methods={"GET"})
     *
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first issue to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of issues to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",               type="string"),
     *         @API\Property(property="subject",          type="string"),
     *         @API\Property(property="author",           type="integer"),
     *         @API\Property(property="author_name",      type="string"),
     *         @API\Property(property="project",          type="integer"),
     *         @API\Property(property="project_name",     type="string"),
     *         @API\Property(property="template",         type="integer"),
     *         @API\Property(property="template_name",    type="string"),
     *         @API\Property(property="state",            type="integer"),
     *         @API\Property(property="state_name",       type="string"),
     *         @API\Property(property="responsible",      type="integer"),
     *         @API\Property(property="responsible_name", type="string"),
     *         @API\Property(property="is_cloned",        type="boolean"),
     *         @API\Property(property="age",              type="integer"),
     *         @API\Property(property="is_critical",      type="boolean"),
     *         @API\Property(property="is_suspended",     type="boolean"),
     *         @API\Property(property="is_closed",        type="boolean")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="subject",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="created_at",  type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="changed_at",  type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="closed_at",   type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="author",      type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="template",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="state",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="responsible", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="age",         type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned issue."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned issue."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found issues."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\Issue::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request                     $request
     * @param IssueRepositoryInterface    $repository
     * @param LastReadRepositoryInterface $lastReadRepository
     *
     * @return JsonResponse
     */
    public function listIssues(
        Request                     $request,
        IssueRepositoryInterface    $repository,
        LastReadRepositoryInterface $lastReadRepository
    ): JsonResponse
    {
        $collection = $this->getCollection($request, $repository);

        /** @var \eTraxis\Entity\LastRead[] $lastReads */
        $lastReads = $lastReadRepository->findBy([
            'issue' => $collection->data,
            'user'  => $this->getUser(),
        ]);

        $values = [];

        foreach ($lastReads as $lastRead) {
            $values[$lastRead->issue->id] = $lastRead->readAt;
        }

        array_walk($collection->data, function (Issue &$issue) use ($values) {
            $readAt = $values[$issue->id] ?? null;
            $issue  = $issue->jsonSerialize();

            $issue[Issue::JSON_READ_AT] = $readAt;
        });

        return $this->json($collection);
    }

    /**
     * Creates new issue.
     *
     * @Route("", name="api_issues_create", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\CreateIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Template is not found.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createIssue(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\CreateIssueCommand($request->request->all());

        /** @var Issue $issue */
        $issue = $commandBus->handle($command);

        $url = $this->generateUrl('api_issues_get', [
            'id' => $issue->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns specified issue.
     *
     * @Route("/{id}", name="api_issues_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @Model(type=eTraxis\Swagger\IssueEx::class))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Issue                       $issue
     * @param IssueRepositoryInterface    $issueRepository
     * @param LastReadRepositoryInterface $lastReadRepository
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return JsonResponse
     */
    public function getIssue(Issue $issue, IssueRepositoryInterface $issueRepository, LastReadRepositoryInterface $lastReadRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        /** @var \eTraxis\Entity\LastRead $lastRead */
        $lastRead = $lastReadRepository->findOneBy([
            'issue' => $issue,
            'user'  => $this->getUser(),
        ]);

        $data = $issue->jsonSerialize();

        $data[Issue::JSON_READ_AT] = $lastRead === null ? null : $lastRead->readAt;

        $data[Issue::JSON_OPTIONS] = [
            IssueVoter::VIEW_ISSUE           => $this->isGranted(IssueVoter::VIEW_ISSUE, $issue),
            IssueVoter::UPDATE_ISSUE         => $this->isGranted(IssueVoter::UPDATE_ISSUE, $issue),
            IssueVoter::DELETE_ISSUE         => $this->isGranted(IssueVoter::DELETE_ISSUE, $issue),
            IssueVoter::CHANGE_STATE         => [],
            IssueVoter::REASSIGN_ISSUE       => [],
            IssueVoter::SUSPEND_ISSUE        => $this->isGranted(IssueVoter::SUSPEND_ISSUE, $issue),
            IssueVoter::RESUME_ISSUE         => $this->isGranted(IssueVoter::RESUME_ISSUE, $issue),
            IssueVoter::ADD_PUBLIC_COMMENT   => $this->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issue),
            IssueVoter::ADD_PRIVATE_COMMENT  => $this->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issue),
            IssueVoter::READ_PRIVATE_COMMENT => $this->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issue),
            IssueVoter::ATTACH_FILE          => $this->isGranted(IssueVoter::ATTACH_FILE, $issue),
            IssueVoter::DELETE_FILE          => $this->isGranted(IssueVoter::DELETE_FILE, $issue),
            IssueVoter::ADD_DEPENDENCY       => $this->isGranted(IssueVoter::ADD_DEPENDENCY, $issue),
            IssueVoter::REMOVE_DEPENDENCY    => $this->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issue),
        ];

        if ($this->isGranted(IssueVoter::CHANGE_STATE, $issue)) {
            $data[Issue::JSON_OPTIONS][IssueVoter::CHANGE_STATE] = array_map(function (State $state) {
                return [
                    'id'          => $state->id,
                    'name'        => $state->name,
                    'type'        => $state->type,
                    'responsible' => $state->responsible,
                ];
            }, $issueRepository->getTransitionsByUser($issue, $this->getUser()));
        }

        if ($this->isGranted(IssueVoter::REASSIGN_ISSUE, $issue)) {
            $data[Issue::JSON_OPTIONS][IssueVoter::REASSIGN_ISSUE] = array_map(function (User $user) {
                return [
                    'id'       => $user->id,
                    'email'    => $user->email,
                    'fullname' => $user->fullname,
                ];
            }, $issueRepository->getResponsiblesByUser($issue, $this->getUser()));
        }

        $lastReadRepository->markAsRead($issue, $this->getUser());

        return $this->json($data);
    }

    /**
     * Clones specified issue.
     *
     * @Route("/{id}", name="api_issues_clone", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\CloneIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function cloneIssue(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\CloneIssueCommand($request->request->all());

        $command->issue = $id;

        /** @var Issue $issue */
        $issue = $commandBus->handle($command);

        $url = $this->generateUrl('api_issues_get', [
            'id' => $issue->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Updates specified issue.
     *
     * @Route("/{id}", name="api_issues_update", methods={"PUT"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\UpdateIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function updateIssue(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UpdateIssueCommand($request->request->all());

        $command->issue = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Deletes specified issue.
     *
     * @Route("/{id}", name="api_issues_delete", methods={"DELETE"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function deleteIssue(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\DeleteIssueCommand([
            'issue' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Changes state of specified issue.
     *
     * @Route("/{id}/state/{state}", name="api_issues_state", methods={"POST"}, requirements={"id": "\d+", "state": "\d+"})
     *
     * @API\Parameter(name="id",    in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="state", in="path", type="integer", required=true, description="State ID.")
     * @API\Parameter(name="",      in="body", @Model(type=Command\ChangeStateCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue or state is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param int        $state
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function changeState(Request $request, int $id, int $state, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\ChangeStateCommand($request->request->all());

        $command->issue = $id;
        $command->state = $state;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Reassigns specified issue.
     *
     * @Route("/{id}/assign/{user}", name="api_issues_assign", methods={"POST"}, requirements={"id": "\d+", "user": "\d+"})
     *
     * @API\Parameter(name="id",   in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="user", in="path", type="integer", required=true, description="User ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue or user is not found.")
     *
     * @param int        $id
     * @param int        $user
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function assignIssue(int $id, int $user, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\ReassignIssueCommand([
            'issue'       => $id,
            'responsible' => $user,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Suspends specified issue.
     *
     * @Route("/{id}/suspend", name="api_issues_suspend", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\SuspendIssueCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function suspendIssue(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\SuspendIssueCommand($request->request->all());

        $command->issue = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Resumes specified issue.
     *
     * @Route("/{id}/resume", name="api_issues_resume", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function resumeIssue(int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\ResumeIssueCommand([
            'issue' => $id,
        ]);

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of issue events.
     *
     * @Route("/{id}/events", name="api_issues_events", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Swagger\Event::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Issue                    $issue
     * @param EventRepositoryInterface $repository
     * @param StateRepositoryInterface $stateRepository
     * @param UserRepositoryInterface  $userRepository
     * @param FileRepositoryInterface  $fileRepository
     * @param IssueRepositoryInterface $issueRepository
     *
     * @return JsonResponse
     */
    public function listEvents(
        Issue                    $issue,
        EventRepositoryInterface $repository,
        StateRepositoryInterface $stateRepository,
        UserRepositoryInterface  $userRepository,
        FileRepositoryInterface  $fileRepository,
        IssueRepositoryInterface $issueRepository
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        // List of used states, users, files, and issues (ORM).
        $ids = [
            Event::JSON_STATE    => [],
            Event::JSON_ASSIGNEE => [],
            Event::JSON_FILE     => [],
            Event::JSON_ISSUE    => [],
        ];

        // List of used states, users, files, and issues (JSON).
        $values = [
            Event::JSON_STATE    => [],
            Event::JSON_ASSIGNEE => [],
            Event::JSON_FILE     => [],
            Event::JSON_ISSUE    => [],
        ];

        // Get list of events.
        $events = $repository->findAllByIssue($issue, $this->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issue));

        // Convert events to JSON representation.
        $data = array_map(function (Event $event) {
            return $event->jsonSerialize();
        }, $events);

        // Find of all used states, users, files, and issues.
        foreach (array_keys($values) as $key) {
            $ids[$key] = array_map(function ($entry) use ($key) {
                return $entry[$key] ?? null;
            }, $data);
        }

        /** @var State[] $states */
        $states = $stateRepository->findBy(['id' => array_unique($ids[Event::JSON_STATE])]);

        /** @var User[] $users */
        $users = $userRepository->findBy(['id' => array_unique($ids[Event::JSON_ASSIGNEE])]);

        /** @var \eTraxis\Entity\File[] $files */
        $files = $fileRepository->findBy(['id' => array_unique($ids[Event::JSON_FILE])]);

        /** @var Issue[] $issues */
        $issues = $issueRepository->findByIds(array_unique($ids[Event::JSON_ISSUE]));

        // Convert states to JSON representation.
        foreach ($states as $state) {
            $values[Event::JSON_STATE][$state->id] = [
                State::JSON_ID          => $state->id,
                State::JSON_NAME        => $state->name,
                State::JSON_TYPE        => $state->type,
                State::JSON_RESPONSIBLE => $state->responsible,
            ];
        }

        // Convert users to JSON representation.
        foreach ($users as $user) {
            /** @var User $user */
            $values[Event::JSON_ASSIGNEE][$user->id] = [
                User::JSON_ID       => $user->id,
                User::JSON_EMAIL    => $user->email,
                User::JSON_FULLNAME => $user->fullname,
            ];
        }

        // Convert files to JSON representation.
        foreach ($files as $file) {
            /** @var \eTraxis\Entity\File $file */
            $values[Event::JSON_FILE][$file->id] = $file->jsonSerialize();
        }

        // Convert issues to JSON representation.
        foreach ($issues as $issue) {
            /** @var Issue $issue */
            $values[Event::JSON_ISSUE][$issue->id] = $issue->jsonSerialize();
            unset($values[Event::JSON_ISSUE][$issue->id][Issue::JSON_READ_AT]);
        }

        // Replace all used IDs with corresponding JSON data.
        array_walk($data, function (&$entry) use ($values) {
            foreach (array_keys($values) as $key) {
                if (array_key_exists($key, $entry)) {
                    $entry[$key] = $values[$key][$entry[$key]] ?? null;
                }
            }
        });

        return $this->json($data);
    }

    /**
     * Returns list of issue changes.
     *
     * @Route("/{id}/changes", name="api_issues_changes", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Swagger\Change::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Issue                           $issue
     * @param ChangeRepositoryInterface       $repository
     * @param DecimalValueRepositoryInterface $decimalRepository
     * @param StringValueRepositoryInterface  $stringRepository
     * @param TextValueRepositoryInterface    $textRepository
     * @param ListItemRepositoryInterface     $listRepository
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return JsonResponse
     */
    public function listChanges(
        Issue                           $issue,
        ChangeRepositoryInterface       $repository,
        DecimalValueRepositoryInterface $decimalRepository,
        StringValueRepositoryInterface  $stringRepository,
        TextValueRepositoryInterface    $textRepository,
        ListItemRepositoryInterface     $listRepository
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        /** @var \Doctrine\Common\Persistence\ObjectRepository[] $repositories */
        $repositories = [
            FieldType::DECIMAL => $decimalRepository,
            FieldType::STRING  => $stringRepository,
            FieldType::TEXT    => $textRepository,
            FieldType::LIST    => $listRepository,
        ];

        $changes = $repository->findAllByIssue($issue, $this->getUser());

        $data = [];

        foreach ($changes as $change) {

            $entry = $change->jsonSerialize();

            if ($change->field === null || array_key_exists($change->field->type, $repositories)) {

                $type = $change->field === null ? FieldType::STRING : $change->field->type;

                /** @var \JsonSerializable $oldValue */
                /** @var \JsonSerializable $newValue */
                $oldValue = $repositories[$type]->find($change->oldValue);
                $newValue = $repositories[$type]->find($change->newValue);

                $entry[Change::JSON_OLD_VALUE] = $oldValue === null ? null : $oldValue->jsonSerialize();
                $entry[Change::JSON_NEW_VALUE] = $newValue === null ? null : $newValue->jsonSerialize();
            }

            $data[] = $entry;
        }

        return $this->json($data);
    }

    /**
     * Returns list of issue watchers.
     *
     * @Route("/{id}/watchers", name="api_issues_watchers", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id",       in="path",  type="integer", required=true,  description="Issue ID.")
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first watcher to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of watchers to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="email",    type="string"),
     *         @API\Property(property="fullname", type="string")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="email",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="fullname", type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned watcher."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned watcher."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found watchers."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\UserInfo::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request                    $request
     * @param Issue                      $issue
     * @param WatcherRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function listWatchers(Request $request, Issue $issue, WatcherRepositoryInterface $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        $filter = json_decode($request->headers->get('X-Filter'), true);
        $filter = is_array($filter) ? $filter : [];

        $request->headers->set('X-Filter', json_encode($filter + ['id' => $issue->id]));

        $collection = $this->getCollection($request, $repository);

        return $this->json($collection);
    }

    /**
     * Returns list of issue comments.
     *
     * @Route("/{id}/comments", name="api_issues_comments_list", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Swagger\Comment::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Issue                      $issue
     * @param CommentRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function listComments(Issue $issue, CommentRepositoryInterface $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        $comments = $repository->findAllByIssue($issue, $this->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issue));

        return $this->json($comments);
    }

    /**
     * Creates new comment.
     *
     * @Route("/{id}/comments", name="api_issues_comments_create", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @Model(type=Command\AddCommentCommand::class, groups={"api"}))
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createComment(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\AddCommentCommand($request->request->all());

        $command->issue = $id;

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Returns list of issue files.
     *
     * @Route("/{id}/files", name="api_files_list", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="array",
     *     @API\Items(
     *         ref=@Model(type=eTraxis\Swagger\File::class)
     *     )
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Issue                   $issue
     * @param FileRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function listFiles(Issue $issue, FileRepositoryInterface $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        $files = $repository->findAllByIssue($issue);

        return $this->json($files);
    }

    /**
     * Attaches new file.
     *
     * @Route("/{id}/files", name="api_files_create", methods={"POST"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id",         in="path",     type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="attachment", in="formData", type="file",    required=true, description="Uploaded file.")
     *
     * @API\Response(response=201, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function createFile(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $attachment */
        $attachment = $request->files->get('attachment');

        $command = new Command\AttachFileCommand([
            'issue' => $id,
            'file'  => $attachment,
        ]);

        /** @var \eTraxis\Entity\File $file */
        $file = $commandBus->handle($command);

        $url = $this->generateUrl('api_files_download', [
            'id' => $file->id,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(null, JsonResponse::HTTP_CREATED, ['Location' => $url]);
    }

    /**
     * Returns list of issue dependencies.
     *
     * @Route("/{id}/dependencies", name="api_issues_dependencies_get", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id",       in="path",  type="integer", required=true,  description="Issue ID.")
     * @API\Parameter(name="offset",   in="query", type="integer", required=false, minimum=0, default=0, description="Zero-based index of the first issue to return.")
     * @API\Parameter(name="limit",    in="query", type="integer", required=false, minimum=1, maximum=100, default=100, description="Maximum number of issues to return.")
     * @API\Parameter(name="X-Search", in="body",  type="string",  required=false, description="Optional search value.", @API\Schema(type="string"))
     * @API\Parameter(name="X-Filter", in="body",  type="object",  required=false, description="Optional filters.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",               type="string"),
     *         @API\Property(property="subject",          type="string"),
     *         @API\Property(property="author",           type="integer"),
     *         @API\Property(property="author_name",      type="string"),
     *         @API\Property(property="project",          type="integer"),
     *         @API\Property(property="project_name",     type="string"),
     *         @API\Property(property="template",         type="integer"),
     *         @API\Property(property="template_name",    type="string"),
     *         @API\Property(property="state",            type="integer"),
     *         @API\Property(property="state_name",       type="string"),
     *         @API\Property(property="responsible",      type="integer"),
     *         @API\Property(property="responsible_name", type="string"),
     *         @API\Property(property="is_cloned",        type="boolean"),
     *         @API\Property(property="age",              type="integer"),
     *         @API\Property(property="is_critical",      type="boolean"),
     *         @API\Property(property="is_suspended",     type="boolean"),
     *         @API\Property(property="is_closed",        type="boolean")
     *     }
     * ))
     * @API\Parameter(name="X-Sort", in="body", type="object", required=false, description="Optional sorting.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="id",          type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="subject",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="created_at",  type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="changed_at",  type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="closed_at",   type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="author",      type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="project",     type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="template",    type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="state",       type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="responsible", type="string", enum={"ASC", "DESC"}, example="ASC"),
     *         @API\Property(property="age",         type="string", enum={"ASC", "DESC"}, example="ASC")
     *     }
     * ))
     *
     * @API\Response(response=200, description="Success.", @API\Schema(
     *     type="object",
     *     properties={
     *         @API\Property(property="from",  type="integer", example=0,   description="Zero-based index of the first returned issue."),
     *         @API\Property(property="to",    type="integer", example=99,  description="Zero-based index of the last returned issue."),
     *         @API\Property(property="total", type="integer", example=100, description="Total number of all found issues."),
     *         @API\Property(property="data",  type="array", @API\Items(
     *             ref=@Model(type=eTraxis\Swagger\Issue::class)
     *         ))
     *     }
     * ))
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request                     $request
     * @param Issue                       $issue
     * @param IssueRepositoryInterface    $repository
     * @param LastReadRepositoryInterface $lastReadRepository
     *
     * @return JsonResponse
     */
    public function getDependencies(
        Request                     $request,
        Issue                       $issue,
        IssueRepositoryInterface    $repository,
        LastReadRepositoryInterface $lastReadRepository
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted(IssueVoter::VIEW_ISSUE, $issue);

        $filter = json_decode($request->headers->get('X-Filter'), true);
        $filter = is_array($filter) ? $filter : [];

        $request->headers->set('X-Filter', json_encode($filter + ['dependency' => $issue->id]));

        $collection = $this->getCollection($request, $repository);

        /** @var \eTraxis\Entity\LastRead[] $lastReads */
        $lastReads = $lastReadRepository->findBy([
            'issue' => $collection->data,
            'user'  => $this->getUser(),
        ]);

        $values = [];

        foreach ($lastReads as $lastRead) {
            $values[$lastRead->issue->id] = $lastRead->readAt;
        }

        array_walk($collection->data, function (Issue &$issue) use ($values) {
            $readAt = $values[$issue->id] ?? null;
            $issue  = $issue->jsonSerialize();

            $issue[Issue::JSON_READ_AT] = $readAt;
        });

        return $this->json($collection);
    }

    /**
     * Updates list of issue dependencies.
     *
     * @Route("/{id}/dependencies", name="api_issues_dependencies_set", methods={"PATCH"}, requirements={"id": "\d+"})
     *
     * @API\Parameter(name="id", in="path", type="integer", required=true, description="Issue ID.")
     * @API\Parameter(name="",   in="body", @API\Schema(
     *     @API\Property(property="add", type="array", example={123, 456}, description="List of issue IDs to add.",
     *         @API\Items(type="integer")
     *     ),
     *     @API\Property(property="remove", type="array", example={123, 456}, description="List of issue IDs to remove.",
     *         @API\Items(type="integer")
     *     )
     * ))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=400, description="The request is malformed.")
     * @API\Response(response=401, description="Client is not authenticated.")
     * @API\Response(response=403, description="Client is not authorized for this request.")
     * @API\Response(response=404, description="Issue is not found.")
     *
     * @param Request    $request
     * @param int        $id
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function setDependencies(Request $request, int $id, CommandBus $commandBus): JsonResponse
    {
        $add    = $request->request->get('add');
        $remove = $request->request->get('remove');

        $add    = is_array($add) ? $add : [];
        $remove = is_array($remove) ? $remove : [];

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();
        $manager->beginTransaction();

        $command = new Command\AddDependenciesCommand([
            'issue'        => $id,
            'dependencies' => array_diff($add, $remove),
        ]);

        if (count($command->dependencies)) {
            $commandBus->handle($command);
        }

        $command = new Command\RemoveDependenciesCommand([
            'issue'        => $id,
            'dependencies' => array_diff($remove, $add),
        ]);

        if (count($command->dependencies)) {
            $commandBus->handle($command);
        }

        $manager->commit();

        return $this->json(null);
    }

    /**
     * Marks specified issues as read.
     *
     * @Route("/read", name="api_issues_read", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\MarkAsReadCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function readIssues(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\MarkAsReadCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Marks specified issues as unread.
     *
     * @Route("/unread", name="api_issues_unread", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\MarkAsUnreadCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function unreadIssues(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\MarkAsUnreadCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Starts watching for specified issues.
     *
     * @Route("/watch", name="api_issues_watch", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\WatchIssuesCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function watchIssues(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\WatchIssuesCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }

    /**
     * Stops watching for specified issues.
     *
     * @Route("/unwatch", name="api_issues_unwatch", methods={"POST"})
     *
     * @API\Parameter(name="", in="body", @Model(type=Command\UnwatchIssuesCommand::class, groups={"api"}))
     *
     * @API\Response(response=200, description="Success.")
     * @API\Response(response=401, description="Client is not authenticated.")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return JsonResponse
     */
    public function unwatchIssues(Request $request, CommandBus $commandBus): JsonResponse
    {
        $command = new Command\UnwatchIssuesCommand($request->request->all());

        $commandBus->handle($command);

        return $this->json(null);
    }
}
