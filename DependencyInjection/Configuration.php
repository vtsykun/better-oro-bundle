<?php

namespace Okvpn\Bundle\BetterOroBundle\DependencyInjection;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('okvpn_better_oro');

        $rootNode
            ->children()
            ->arrayNode('default_priorities')
                ->beforeNormalization()
                    ->always(function ($value) {
                        $value = array_map([$this, 'normalizePriority'], $value);
                        return $value;
                    })
                ->end()
                ->useAttributeAsKey('name')
                ->prototype('scalar')->end()
            ->end()
            ->integerNode('prefetch_size')->defaultValue(4)
            ->end();
        return $treeBuilder;
    }

    protected function normalizePriority($integerPriority)
    {
        $mapping = [
            MessagePriority::VERY_LOW,
            MessagePriority::LOW,
            MessagePriority::NORMAL,
            MessagePriority::HIGH,
            MessagePriority::VERY_HIGH
        ];

        switch (true) {
            case (isset($mapping[$integerPriority])):
                return $mapping[$integerPriority];
            case ($integerPriority > 4):
                return MessagePriority::VERY_HIGH;
            case ($integerPriority < 0):
                return MessagePriority::VERY_LOW;
            default:
                return MessagePriority::NORMAL;
        }
    }
}
