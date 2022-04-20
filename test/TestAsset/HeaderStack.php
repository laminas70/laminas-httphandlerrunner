<?php

declare(strict_types=1);

namespace LaminasTest\HttpHandlerRunner\TestAsset;

/**
 * Store output artifacts
 *
 * @psalm-type HeaderType = array{header:string,replace:bool,status_code:int|null}
 */
class HeaderStack
{
    /**
     * @var string[][]
     * @psalm-var list<HeaderType>
     */
    private static $data = [];

    /**
     * Reset state
     * @return void
     */
    public static function reset()
    {
        self::$data = [];
    }

    /**
     * Push a header on the stack
     *
     * @param string[] $header
     * @psalm-param HeaderType $header
     * @return void
     */
    public static function push($header)
    {
        self::$data[] = $header;
    }

    /**
     * Return the current header stack
     *
     * @return string[][]
     * @psalm-return list<HeaderType>
     */
    public static function stack()
    {
        return self::$data;
    }

    /**
     * Verify if there's a header line on the stack
     * @param string $header
     */
    public static function has($header): bool
    {
        foreach (self::$data as $item) {
            if ($item['header'] === $header) {
                return true;
            }
        }

        return false;
    }
}
