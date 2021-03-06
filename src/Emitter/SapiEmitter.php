<?php

declare(strict_types=1);

namespace Laminas\HttpHandlerRunner\Emitter;

use Psr\Http\Message\ResponseInterface;

class SapiEmitter implements EmitterInterface
{
    use SapiEmitterTrait;

    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function emit($response): bool
    {
        $this->assertNoPreviousOutput();

        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);

        return true;
    }

    /**
     * Emit the message body.
     * @return void
     */
    private function emitBody(ResponseInterface $response)
    {
        echo $response->getBody();
    }
}
