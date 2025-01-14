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

namespace eTraxis\CommandBus\Command\Users;

use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @covers \eTraxis\CommandBus\CommandHandler\Users\UpdateSettingsHandler::handle
 */
class UpdateSettingsCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\Repository\Contracts\UserRepositoryInterface */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');

        self::assertSame('en_US', $user->locale);
        self::assertSame('azure', $user->theme);
        self::assertSame('UTC', $user->timezone);

        $command = new UpdateSettingsCommand([
            'locale'   => 'ru',
            'theme'    => 'humanity',
            'timezone' => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('ru', $user->locale);
        self::assertSame('humanity', $user->theme);
        self::assertSame('Pacific/Auckland', $user->timezone);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $command = new UpdateSettingsCommand([
            'locale'   => 'ru',
            'theme'    => 'humanity',
            'timezone' => 'Pacific/Auckland',
        ]);

        $this->commandBus->handle($command);
    }
}
