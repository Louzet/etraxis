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

namespace eTraxis\Security\Authenticator;

use eTraxis\Entity\User;
use eTraxis\ReflectionTrait;
use eTraxis\TransactionalTestCase;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @coversDefaultClass \eTraxis\Security\Authenticator\GoogleOAuth2Authenticator
 */
class GoogleOAuth2AuthenticatorTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var RouterInterface */
    protected $router;

    /** @var SessionInterface */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->router  = $this->client->getContainer()->get('router');
        $this->session = $this->client->getContainer()->get('session');
    }

    /**
     * @covers ::__construct
     * @covers ::getProvider
     */
    public function testGetProvider()
    {
        $authenticator = new GoogleOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret', 'example.com');
        self::assertInstanceOf(Google::class, $this->callMethod($authenticator, 'getProvider'));

        $authenticator = new GoogleOAuth2Authenticator($this->router, $this->session, $this->commandBus, '', 'secret', 'example.com');
        self::assertNull($this->callMethod($authenticator, 'getProvider'));
    }

    /**
     * @covers ::getScope
     */
    public function testGetScope()
    {
        $expected = [];

        $authenticator = new GoogleOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret', 'example.com');
        self::assertSame($expected, $this->callMethod($authenticator, 'getScope'));
    }

    /**
     * @covers ::getUserFromToken
     */
    public function testGetUserFromToken()
    {
        $owner = new GoogleUser([
            'sub'   => '423729',
            'email' => 'anna@example.com',
            'name'  => 'Anna Rodygina',
        ]);

        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getResourceOwner')
            ->willReturn($owner);

        $authenticator = new GoogleOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret', 'example.com');
        $this->setProperty($authenticator, 'provider', $provider);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($entity);

        $user = $this->callMethod($authenticator, 'getUserFromToken', ['token' => $this->createMock(AccessToken::class)]);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNotNull($entity);

        self::assertSame($entity, $user);
    }
}
