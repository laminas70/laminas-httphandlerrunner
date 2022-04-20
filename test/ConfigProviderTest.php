<?php

declare(strict_types=1);

namespace LaminasTest\HttpHandlerRunner;

use Laminas\HttpHandlerRunner\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $provider;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->provider = new ConfigProvider();
    }

    /**
     * @return void
     */
    public function testReturnedArrayContainsDependencies()
    {
        $config = ($this->provider)();
        self::assertArrayHasKey('dependencies', $config);
        self::assertIsArray($config['dependencies']);
    }
}
