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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiGroupsController;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateGroupTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Group $group */
        $group = $this->doctrine->getRepository(Group::class)->findOneBy(['name' => 'Testers']);
        self::assertNull($group);

        $data = [
            'project'     => $project->id,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        $group = $this->doctrine->getRepository(Group::class)->findOneBy(['name' => 'Testers']);
        self::assertNotNull($group);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isRedirect("http://localhost/api/groups/{$group->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $this->loginAs('artem@example.com');

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $data = [
            'project'     => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ];

        $this->loginAs('artem@example.com');

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test409()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Managers',
            'description' => 'Project management',
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/groups';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
