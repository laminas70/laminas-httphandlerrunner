<?php

declare(strict_types=1);

namespace LaminasTest\HttpHandlerRunner\Emitter;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use SplStack;

class EmitterStackTest extends TestCase
{
    /** @var EmitterStack */
    private $emitter;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->emitter = new EmitterStack();
    }

    /**
     * @return void
     */
    public function testIsAnSplStack()
    {
        $this->assertInstanceOf(SplStack::class, $this->emitter);
    }

    /**
     * @return void
     */
    public function testIsAnEmitterImplementation()
    {
        $this->assertInstanceOf(EmitterInterface::class, $this->emitter);
    }

    /** @return mixed[] */
    public function nonEmitterValues()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['emitter'],
            'array'      => [[$this->createMock(EmitterInterface::class)]],
            'object'     => [(object) []],
        ];
    }

    /**
     * @dataProvider nonEmitterValues
     * @param mixed $value
     * @return void
     */
    public function testCannotPushNonEmitterToStack($value)
    {
        $this->expectException(Exception\InvalidEmitterException::class);
        /** @psalm-suppress MixedArgument */
        $this->emitter->push($value);
    }

    /**
     * @dataProvider nonEmitterValues
     * @param mixed $value
     * @return void
     */
    public function testCannotUnshiftNonEmitterToStack($value)
    {
        $this->expectException(Exception\InvalidEmitterException::class);
        /** @psalm-suppress MixedArgument */
        $this->emitter->unshift($value);
    }

    /**
     * @dataProvider nonEmitterValues
     * @param mixed $value
     * @return void
     */
    public function testCannotSetNonEmitterToSpecificIndex($value)
    {
        $this->expectException(Exception\InvalidEmitterException::class);
        /** @psalm-suppress MixedArgument */
        $this->emitter->offsetSet(0, $value);
    }

    /**
     * @return void
     */
    public function testOffsetSetReplacesExistingValue()
    {
        $first       = $this->createMock(EmitterInterface::class);
        $replacement = $this->createMock(EmitterInterface::class);
        $this->emitter->push($first);
        $this->emitter->offsetSet(0, $replacement);
        $this->assertSame($replacement, $this->emitter->pop());
    }

    /**
     * @return void
     */
    public function testUnshiftAddsNewEmitter()
    {
        $first  = $this->createMock(EmitterInterface::class);
        $second = $this->createMock(EmitterInterface::class);
        $this->emitter->push($first);
        $this->emitter->unshift($second);
        $this->assertSame($first, $this->emitter->pop());
    }

    /**
     * @return void
     */
    public function testEmitLoopsThroughEmittersUntilOneReturnsTrueValue()
    {
        $first = $this->createMock(EmitterInterface::class);
        $first->expects($this->never())->method('emit');

        $second = $this->createMock(EmitterInterface::class);
        $second->method('emit')->with($this->isInstanceOf(ResponseInterface::class))->willReturn(true);

        $third = $this->createMock(EmitterInterface::class);
        $third->method('emit')->with($this->isInstanceOf(ResponseInterface::class))->willReturn(false);

        $this->emitter->push($first);
        $this->emitter->push($second);
        $this->emitter->push($third);

        $response = $this->createMock(ResponseInterface::class);

        $this->assertTrue($this->emitter->emit($response));
    }

    /**
     * @return void
     */
    public function testEmitReturnsFalseIfLastEmmitterReturnsFalse()
    {
        $first = $this->createMock(EmitterInterface::class);
        $first->method('emit')->with($this->isInstanceOf(ResponseInterface::class))->willReturn(false);

        $this->emitter->push($first);

        $response = $this->createMock(ResponseInterface::class);

        $this->assertFalse($this->emitter->emit($response));
    }

    /**
     * @return void
     */
    public function testEmitReturnsFalseIfNoEmittersAreComposed()
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->assertFalse($this->emitter->emit($response));
    }
}
