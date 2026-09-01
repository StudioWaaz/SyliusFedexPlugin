<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Waaz\SyliusFedexPlugin\WaazSyliusFedexPlugin;

final class PluginTest extends TestCase
{
    public function testPluginIsBundle(): void
    {
        $plugin = new WaazSyliusFedexPlugin();
        $this->assertInstanceOf(Bundle::class, $plugin);
    }
}
