<?php

declare(strict_types=1);

namespace Waaz\SyliusFedexPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('waaz_sylius_fedex');
        $rootNode = $treeBuilder->getRootNode();
        $this->addGlobalSection($rootNode);

        return $treeBuilder;
    }

    private function addGlobalSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->booleanNode('sandbox')
                    ->defaultTrue()
                ->end()
                ->enumNode('weight_unit')
                    ->cannotBeEmpty()
                    ->values(['KG', 'LB'])
                    ->defaultValue('KG')
                ->end()
            ->end()
        ;
    }
}
