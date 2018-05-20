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

namespace eTraxis\TemplatesDomain\Application\Command\Fields;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateFieldCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new UpdateIssueFieldCommand([
            'field'       => $field->id,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandbus->handle($command);

        /** @var Field $field */
        $field = $this->repository->find($field->id);

        self::assertSame('Task ID', $field->name);
        self::assertSame('ID of the duplicating task.', $field->description);
        self::assertTrue($field->isRequired);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new UpdateIssueFieldCommand([
            'field'       => $field->id,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */,  /* skipping */, $field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new UpdateIssueFieldCommand([
            'field'       => $field->id,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateIssueFieldCommand([
            'field'       => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandbus->handle($command);
    }

    public function testRemovedField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'ASC']);

        $command = new UpdateIssueFieldCommand([
            'field'       => $field->id,
            'name'        => 'Task ID',
            'description' => 'ID of the duplicating task.',
            'required'    => true,
        ]);

        $this->commandbus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Field with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new UpdateListFieldCommand([
            'field'       => $field->id,
            'name'        => 'Description',
            'required'    => true,
        ]);

        $this->commandbus->handle($command);
    }
}
