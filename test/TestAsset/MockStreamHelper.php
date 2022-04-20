<?php

declare(strict_types=1);

namespace LaminasTest\HttpHandlerRunner\TestAsset;

use function is_callable;
use function strlen;
use function substr;

use const SEEK_SET;

class MockStreamHelper
{
    /** @var string|callable(int,?int=null):string */
    private $contents;

    /** @var int */
    private $position;

    /** @var int */
    private $size;

    /** @var int */
    private $startPosition;

    /** @var null|callable */
    private $trackPeakBufferLength;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     * @param string|callable(int,?int=null):string $contents
     */
    public function __construct(
        $contents,
        int $size,
        int $startPosition,
        $trackPeakBufferLength = null
    ) {
        $this->contents              = $contents;
        $this->size                  = $size;
        $this->position              = $startPosition;
        $this->startPosition         = $startPosition;
        $this->trackPeakBufferLength = $trackPeakBufferLength;
    }

    public function handleToString(): string
    {
        $this->position = $this->size;
        return is_callable($this->contents) ? ($this->contents)(0) : $this->contents;
    }

    public function handleTell(): int
    {
        return $this->position;
    }

    public function handleEof(): bool
    {
        return $this->position >= $this->size;
    }

    /**
     * @param int $offset
     * @param int|null $whence
     */
    public function handleSeek($offset, $whence = SEEK_SET): bool
    {
        if ($offset >= $this->size) {
            return false;
        }

        $this->position = $offset;
        return true;
    }

    public function handleRewind(): bool
    {
        $this->position = 0;
        return true;
    }

    /**
     * @param int $length
     */
    public function handleRead($length): string
    {
        if ($this->trackPeakBufferLength) {
            ($this->trackPeakBufferLength)($length);
        }

        $data = is_callable($this->contents)
            ? ($this->contents)($this->position, $length)
            : substr($this->contents, $this->position, $length);

        $this->position += strlen($data);

        return $data;
    }

    public function handleGetContents(): string
    {
        $remainingContents = is_callable($this->contents)
            ? ($this->contents)($this->position)
            : substr($this->contents, $this->position);

        $this->position += strlen($remainingContents);

        return $remainingContents;
    }
}
