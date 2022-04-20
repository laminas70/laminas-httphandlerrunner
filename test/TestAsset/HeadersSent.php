<?php // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps


declare(strict_types=1);

namespace Laminas\HttpHandlerRunner\Emitter;

use LaminasTest\HttpHandlerRunner\TestAsset\HeaderStack;

final class HeadersSent
{
    /** @var bool */
    private static $headerSent = false;
    /** @var null|string */
    public static $filename;
    /** @var null|int */
    public static $line;

    /**
     * @return void
     */
    public static function reset()
    {
        self::$headerSent = false;
        self::$filename   = null;
        self::$line       = null;
    }

    /**
     * @return void
     */
    public static function markSent(string $filename, int $line)
    {
        self::$headerSent = true;
        self::$filename   = $filename;
        self::$line       = $line;
    }

    public static function sent(): bool
    {
        return self::$headerSent;
    }
}

/**
 * @param string|null $filename
 * @param int|null $line
 */
function headers_sent(&$filename = null, &$line = null): bool
{
    $filename = HeadersSent::$filename;
    $line     = HeadersSent::$line;
    return HeadersSent::sent();
}

/**
 * Emit a header, without creating actual output artifacts
 * @param int|null $httpResponseCode
 * @return void
 */
function header(string $headerName, bool $replace = true, $httpResponseCode = null)
{
    HeaderStack::push(
        [
            'header'      => $headerName,
            'replace'     => $replace,
            'status_code' => $httpResponseCode,
        ]
    );
}
