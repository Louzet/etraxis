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

namespace eTraxis\CommandBus\Command\Templates;

use eTraxis\Entity\Template;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Templates\LockTemplateHandler::handle
 */
class LockTemplateCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\TemplateRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testLockTemplate()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        self::assertFalse($template->isLocked);

        $command = new LockTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertTrue($template->isLocked);
    }

    public function testIdempotence()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        self::assertTrue($template->isLocked);

        $command = new LockTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertTrue($template->isLocked);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $command = new LockTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new LockTemplateCommand([
            'template' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }
}
