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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiTemplatesController;

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateTemplateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Template $template */
        $template = $this->doctrine->getRepository(Template::class)->findOneBy(['name' => 'Bugfix']);
        self::assertNull($template);

        $data = [
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        $template = $this->doctrine->getRepository(Template::class)->findOneBy(['name' => 'Bugfix']);
        self::assertNotNull($template);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isRedirect("http://localhost/api/templates/{$template->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->loginAs('artem@example.com');

        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test409()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $data = [
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'task',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
