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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController;

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController::createField
 */
class CreateFieldTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->doctrine->getRepository(Field::class)->findOneBy(['name' => 'Week number']);
        self::assertNull($field);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        $field = $this->doctrine->getRepository(Field::class)->findOneBy(['name' => 'Week number']);
        self::assertNotNull($field);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isRedirect("http://localhost/api/fields/{$field->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'state'    => self::UNKNOWN_ENTITY_ID,
            'type'     => FieldType::NUMBER,
            'name'     => 'Week number',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'state'    => $state->id,
            'type'     => FieldType::NUMBER,
            'name'     => 'Due date',
            'required' => true,
            'minimum'  => 1,
            'maximum'  => 53,
            'default'  => 7,
        ];

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
