<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2014-2016 Artem Rodygin
//
//  You should have received a copy of the GNU General Public License
//  along with the file. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Subscriber;

use eTraxis\Entity\User;
use eTraxis\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * @coversDefaultClass \eTraxis\Subscriber\StickyLocale
 */
class StickyLocaleTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $request_stack;

    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->request_stack = $this->client->getContainer()->get('request_stack');
        $this->session       = $this->client->getContainer()->get('session');
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents()
    {
        $expected = [
            'security.interactive_login',
            'security.switch_user',
            'kernel.request',
        ];

        self::assertSame($expected, array_keys(StickyLocale::getSubscribedEvents()));
    }

    /**
     * @covers ::saveLocale
     */
    public function testSaveLocale()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $user->locale = 'ru';

        $request = new Request();
        $token   = new UsernamePasswordToken($user, null, 'etraxis_provider');

        $event = new InteractiveLoginEvent($request, $token);

        $object = new StickyLocale($this->session, 'en');
        $object->saveLocale($event);

        self::assertSame('ru', $this->session->get('_locale'));
    }

    /**
     * @covers ::onSwitchUser
     */
    public function testOnSwitchUser()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $user->locale = 'ru';

        $request = new Request();
        $request->setSession($this->session);

        $event = new SwitchUserEvent($request, $user);

        $object = new StickyLocale($this->session, 'en');
        $object->onSwitchUser($event);

        self::assertSame('ru', $this->session->get('_locale'));
    }

    /**
     * @covers ::setLocale
     */
    public function testSetDefaultLocale()
    {
        $request = new Request();

        $request->setSession($this->session);
        $request->cookies->set($this->session->getName(), $this->session->getId());

        $this->request_stack->push($request);

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $object = new StickyLocale($this->session, 'ru');

        $object->setLocale($event);

        self::assertSame('ru', $event->getRequest()->getLocale());
    }

    /**
     * @covers ::setLocale
     */
    public function testSetLocaleBySession()
    {
        $request = new Request();

        $request->setSession($this->session);
        $request->cookies->set($this->session->getName(), $this->session->getId());
        $this->session->set('_locale', 'ja');

        $this->request_stack->push($request);

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $object = new StickyLocale($this->session, 'ru');

        $object->setLocale($event);

        self::assertSame('ja', $event->getRequest()->getLocale());
    }
}
