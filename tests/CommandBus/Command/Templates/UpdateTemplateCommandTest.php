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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Templates\UpdateTemplateHandler::handle
 */
class UpdateTemplateCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\TemplateRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);

        /** @var Template $template */
        $template = $this->repository->find($template->id);

        self::assertSame('Bugfix', $template->name);
        self::assertSame('bug', $template->prefix);
        self::assertSame('Error reports', $template->description);
        self::assertSame(5, $template->criticalAge);
        self::assertSame(10, $template->frozenTime);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateTemplateCommand([
            'template'    => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Support',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }

    public function testPrefixConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified prefix already exists.');

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'template'    => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'req',
            'description' => 'Error reports',
            'critical'    => 5,
            'frozen'      => 10,
        ]);

        $this->commandBus->handle($command);
    }
}
