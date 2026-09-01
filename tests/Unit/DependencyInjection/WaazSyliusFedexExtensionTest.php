<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusFedexPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Waaz\SyliusFedexPlugin\DependencyInjection\WaazSyliusFedexExtension;

final class WaazSyliusFedexExtensionTest extends TestCase
{
    public function testLoadExtension(): void
    {
        $container = new ContainerBuilder();
        $extension = new WaazSyliusFedexExtension();

        $extension->load([], $container);

        $this->assertTrue($container->hasParameter('waaz_sylius_fedex_plugin.sandbox'));
        $this->assertEquals('KG', $container->getParameter('waaz_sylius_fedex_plugin.weight_unit'));
        $this->assertTrue($container->has('waaz.fedex_plugin.api.client'));
        $this->assertTrue($container->has('waaz.fedex_plugin.form.type.fedex_shipping_gateway'));
        $this->assertTrue($container->has('waaz.fedex_plugin.provider.fedex'));
    }
}
