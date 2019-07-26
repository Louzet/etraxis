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

use eTraxis\Entity\Issue;
use eTraxis\Entity\State;
use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\Controller\API\ApiIssuesController::getIssue
 */
class GetIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4']);

        /** @var State $resolved */
        [/* skipping */, /* skipping */, $resolved] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved']);

        /** @var User $author */
        $author = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dtillman@example.com']);

        /** @var User $responsible */
        $responsible = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'cbatz@example.com']);

        /** @var User $kbahringer */
        $kbahringer = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'kbahringer@example.com']);

        /** @var User $tbuckridge */
        $tbuckridge = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tbuckridge@example.com']);

        /** @var User $tmarquardt */
        $tmarquardt = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        $expected = [
            'id'           => $issue->id,
            'subject'      => 'Support request 4',
            'created_at'   => $issue->createdAt,
            'changed_at'   => $issue->changedAt,
            'closed_at'    => null,
            'author'       => [
                'id'       => $author->id,
                'email'    => 'dtillman@example.com',
                'fullname' => 'Derrick Tillman',
            ],
            'state'        => [
                'id'          => $issue->state->id,
                'template'    => [
                    'id'          => $issue->state->template->id,
                    'project'     => [
                        'id'          => $issue->state->template->project->id,
                        'name'        => 'Excepturi',
                        'description' => 'Project C',
                        'created'     => $issue->state->template->project->createdAt,
                        'suspended'   => false,
                    ],
                    'name'        => 'Support',
                    'prefix'      => 'req',
                    'description' => 'Support Request C',
                    'critical'    => 3,
                    'frozen'      => 7,
                    'locked'      => false,
                ],
                'name'        => 'Opened',
                'type'        => 'intermediate',
                'responsible' => 'assign',
                'next'        => null,
            ],
            'responsible'  => [
                'id'       => $responsible->id,
                'email'    => 'cbatz@example.com',
                'fullname' => 'Carter Batz',
            ],
            'is_cloned'    => false,
            'origin'       => null,
            'age'          => $issue->age,
            'is_critical'  => true,
            'is_suspended' => false,
            'resumes_at'   => null,
            'is_closed'    => false,
            'is_frozen'    => false,
            'read_at'      => null,
            'options'      => [
                'issue.view'           => true,
                'issue.update'         => true,
                'issue.delete'         => true,
                'state.change'         => [
                    [
                        'id'          => $resolved->id,
                        'name'        => $resolved->name,
                        'type'        => $resolved->type,
                        'responsible' => $resolved->responsible,
                    ],
                ],
                'issue.reassign'       => [
                    [
                        'id'       => $kbahringer->id,
                        'email'    => 'kbahringer@example.com',
                        'fullname' => 'Kailyn Bahringer',
                    ],
                    [
                        'id'       => $tbuckridge->id,
                        'email'    => 'tbuckridge@example.com',
                        'fullname' => 'Tony Buckridge',
                    ],
                    [
                        'id'       => $tmarquardt->id,
                        'email'    => 'tmarquardt@example.com',
                        'fullname' => 'Tracy Marquardt',
                    ],
                ],
                'issue.suspend'        => true,
                'issue.resume'         => false,
                'comment.public.add'   => true,
                'comment.private.add'  => true,
                'comment.private.read' => true,
                'file.attach'          => true,
                'file.delete'          => true,
                'dependency.add'       => true,
                'dependency.remove'    => true,
            ],
        ];

        $uri = sprintf('/api/issues/%s', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6']);

        $uri = sprintf('/api/issues/%s', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6']);

        $uri = sprintf('/api/issues/%s', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('nhills@example.com');

        $uri = sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
