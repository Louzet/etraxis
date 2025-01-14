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

namespace eTraxis\CommandBus\Command\States;

use eTraxis\Dictionary\SystemRole;
use eTraxis\Entity\State;
use eTraxis\Entity\StateRoleTransition;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\States\SetRolesTransitionHandler::handle
 */
class SetRolesTransitionCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\StateRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $before = [
            SystemRole::AUTHOR,
            SystemRole::RESPONSIBLE,
        ];

        $after = [
            SystemRole::ANYONE,
            SystemRole::RESPONSIBLE,
        ];

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        self::assertSame($before, $this->transitionsToArray($fromState->roleTransitions, $toState));

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($fromState);
        self::assertSame($after, $this->transitionsToArray($fromState->roleTransitions, $toState));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [/* skipping */,  /* skipping */, $fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [/* skipping */, /* skipping */, $toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownFromState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => self::UNKNOWN_ENTITY_ID,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownToState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => self::UNKNOWN_ENTITY_ID,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongStates()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('States must belong the same template.');

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->repository->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->repository->findBy(['name' => 'Resolved'], ['id' => 'DESC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @param StateRoleTransition[] $transitions
     * @param State                 $state
     *
     * @return string[]
     */
    protected function transitionsToArray(array $transitions, State $state): array
    {
        $filtered = array_filter($transitions, function (StateRoleTransition $transition) use ($state) {
            return $transition->toState === $state;
        });

        $result = array_map(function (StateRoleTransition $transition) {
            return $transition->role;
        }, $filtered);

        sort($result);

        return $result;
    }
}
