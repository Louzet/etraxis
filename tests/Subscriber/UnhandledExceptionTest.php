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

namespace eTraxis\Subscriber;

use eTraxis\Serializer\ConstraintViolationsNormalizer;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @coversDefaultClass \eTraxis\Subscriber\UnhandledException
 */
class UnhandledExceptionTest extends TestCase
{
    /** @var UnhandledException */
    protected $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $logger = new NullLogger();

        /** @var \PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnMap([
                ['Authentication required.', [], null, null, 'User-friendly 401 error message.'],
                ['http_error.403.description', [], null, null, 'User-friendly 403 error message.'],
                ['http_error.404.description', [], null, null, 'User-friendly 404 error message.'],
            ]);

        $normalizer = new ConstraintViolationsNormalizer();

        /** @var TranslatorInterface $translator */
        $this->subscriber = new UnhandledException($logger, $translator, $normalizer);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents()
    {
        $expected = [
            'kernel.exception',
        ];

        self::assertSame($expected, array_keys(UnhandledException::getSubscribedEvents()));
    }

    /**
     * @covers ::onException
     */
    public function testMasterRequest()
    {
        $request = new Request();

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new HttpException(Response::HTTP_NOT_FOUND, 'Unknown username.')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();

        self::assertNull($response);
    }

    /**
     * @covers ::onException
     */
    public function testInvalidCommandException()
    {
        $expected = [
            [
                'property' => 'property',
                'value'    => '0',
                'message'  => 'This value should be "1" or more.',
            ],
        ];

        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $command = new class() {
            /** @Range(min="1", max="100") */
            public $property = 0;
        };

        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation(
            'This value should be "1" or more.',
            'This value should be {{ limit }} or more.',
            [
                '{{ value }}' => '"0"',
                '{{ limit }}' => '"1"',
            ],
            $command,
            'property',
            '0'
        ));

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            InvalidCommandException::onCommand($command, $violations)
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame($expected, json_decode($content, true));
    }

    /**
     * @covers ::getHttpErrorMessage
     * @covers ::onException
     */
    public function testHttp401Exception()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new UnauthorizedHttpException('')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('User-friendly 401 error message.', trim($content, '"'));
    }

    /**
     * @covers ::getHttpErrorMessage
     * @covers ::onException
     */
    public function testHttp403Exception()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new AccessDeniedHttpException()
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('User-friendly 403 error message.', trim($content, '"'));
    }

    /**
     * @covers ::getHttpErrorMessage
     * @covers ::onException
     */
    public function testHttp404Exception()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new NotFoundHttpException()
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('User-friendly 404 error message.', trim($content, '"'));
    }

    /**
     * @covers ::getHttpErrorMessage
     * @covers ::onException
     */
    public function testHttpDefaultMessageException()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new ConflictHttpException()
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR], trim($content, '"'));
    }

    /**
     * @covers ::onException
     */
    public function testHttpCustomMessageException()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new AccessDeniedHttpException('You are not allowed for this action.')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('You are not allowed for this action.', trim($content, '"'));
    }

    /**
     * @covers ::onException
     */
    public function testException()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new \Exception('Something went wrong.')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('Something went wrong.', trim($content, '"'));
    }
}
