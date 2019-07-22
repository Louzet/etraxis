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

use eTraxis\CommandBus\Command\Issues\DeleteFileCommand;
use eTraxis\Dictionary\EventType;
use eTraxis\Entity\Event;
use eTraxis\Repository\EventRepository;
use eTraxis\Repository\FileRepository;
use eTraxis\Voter\IssueVoter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteFileHandler
{
    protected $security;
    protected $tokens;
    protected $eventRepository;
    protected $fileRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param EventRepository               $eventRepository
     * @param FileRepository                $fileRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        EventRepository               $eventRepository,
        FileRepository                $fileRepository
    )
    {
        $this->security        = $security;
        $this->tokens          = $tokens;
        $this->eventRepository = $eventRepository;
        $this->fileRepository  = $fileRepository;
    }

    /**
     * Command handler.
     *
     * @param DeleteFileCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(DeleteFileCommand $command): void
    {
        /** @var \eTraxis\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|\eTraxis\Entity\File $file */
        $file = $this->fileRepository->find($command->file);

        if (!$file || $file->isRemoved) {
            throw new NotFoundHttpException('Unknown file.');
        }

        if (!$this->security->isGranted(IssueVoter::DELETE_FILE, $file->event->issue)) {
            throw new AccessDeniedHttpException('You are not allowed to delete this file.');
        }

        $event = new Event(EventType::FILE_DELETED, $file->event->issue, $user, $file->id);

        $file->remove();

        $this->eventRepository->persist($event);
        $this->fileRepository->persist($file);

        $filename = $this->fileRepository->getFullPath($file);

        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}
