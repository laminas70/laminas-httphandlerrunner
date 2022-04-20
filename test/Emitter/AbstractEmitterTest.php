<?php

declare(strict_types=1);

namespace LaminasTest\HttpHandlerRunner\Emitter;

use Laminas\Diactoros\Response;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\HeadersSent;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Exception\EmitterException;
use LaminasTest\HttpHandlerRunner\TestAsset\HeaderStack;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use function ob_end_clean;
use function ob_start;
use function sprintf;

abstract class AbstractEmitterTest extends TestCase
{
    /** @var EmitterInterface */
    protected $emitter;

    /**
     * @return void
     */
    public function setUp()
    {
        HeaderStack::reset();
        HeadersSent::reset();
        $this->emitter = new SapiEmitter();
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        HeaderStack::reset();
        HeadersSent::reset();
    }

    /**
     * @return void
     */
    public function testEmitsResponseHeaders()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        ob_start();
        $this->emitter->emit($response);
        ob_end_clean();

        self::assertTrue(HeaderStack::has('HTTP/1.1 200 OK'));
        self::assertTrue(HeaderStack::has('Content-Type: text/plain'));
    }

    /**
     * @return void
     */
    public function testEmitsMessageBody()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        $this->expectOutputString('Content!');
        $this->emitter->emit($response);
    }

    /**
     * @return void
     */
    public function testMultipleSetCookieHeadersAreNotReplaced()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Set-Cookie', 'foo=bar')
            ->withAddedHeader('Set-Cookie', 'bar=baz');

        $this->emitter->emit($response);

        $expectedStack = [
            ['header' => 'Set-Cookie: foo=bar', 'replace' => false, 'status_code' => 200],
            ['header' => 'Set-Cookie: bar=baz', 'replace' => false, 'status_code' => 200],
            ['header' => 'HTTP/1.1 200 OK', 'replace' => true, 'status_code' => 200],
        ];

        self::assertSame($expectedStack, HeaderStack::stack());
    }

    /**
     * @return void
     */
    public function testDoesNotLetResponseCodeBeOverriddenByPHP()
    {
        $response = (new Response())
            ->withStatus(202)
            ->withAddedHeader('Location', 'http://api.my-service.com/12345678')
            ->withAddedHeader('Content-Type', 'text/plain');

        $this->emitter->emit($response);

        $expectedStack = [
            ['header' => 'Location: http://api.my-service.com/12345678', 'replace' => true, 'status_code' => 202],
            ['header' => 'Content-Type: text/plain', 'replace' => true, 'status_code' => 202],
            ['header' => 'HTTP/1.1 202 Accepted', 'replace' => true, 'status_code' => 202],
        ];

        self::assertSame($expectedStack, HeaderStack::stack());
    }

    /**
     * @return void
     */
    public function testDoesNotInjectContentLengthHeaderIfStreamSizeIsUnknown()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('Content!');
        $stream->method('getSize')->willReturn(null);
        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream);

        ob_start();
        $this->emitter->emit($response);
        ob_end_clean();
        foreach (HeaderStack::stack() as $header) {
            self::assertStringNotContainsString('Content-Length:', $header['header']);
        }
    }

    /**
     * @return void
     */
    public function testWillThrowEmitterExceptionWhenHeadersAreAlreadySent()
    {
        $sentInLine = __LINE__;
        HeadersSent::markSent(__FILE__, $sentInLine);

        $this->expectException(EmitterException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to emit response; headers already sent in %s:%d',
                __FILE__,
                $sentInLine
            )
        );
        $this->emitter->emit($this->createMock(ResponseInterface::class));
    }
}
