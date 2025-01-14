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

namespace eTraxis\Controller\ApiIssuesController;

use eTraxis\Entity\Field;
use eTraxis\Entity\Issue;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ApiIssuesController::cloneIssue
 */
class CloneIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNotNull($issue);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isRedirect("http://localhost/api/issues/{$issue->id}"));
    }

    public function test400()
    {
        $this->loginAs('nhills@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $origin */
        [/* skipping */, /* skipping */, $origin] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', $origin->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('nhills@example.com');

        /** @var Field $field */
        [/* skipping */, /* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Issue $issue */
        $issue = $this->doctrine->getRepository(Issue::class)->findOneBy(['subject' => 'Test issue']);
        self::assertNull($issue);

        $data = [
            'subject' => 'Test issue',
            'fields'  => [
                $field->id => 2,
            ],
        ];

        $uri = sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
