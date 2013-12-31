<?php

namespace Lioshi\WonderCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Defines the configuration options for the Memcached object
 * Based on Lsw\MemcacheBundle by LeaseWeb
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('lioshi_wonder_cache');
        $rootNode
            ->children()
                ->scalarNode('activated')
                    ->info('Specify to enable WonderCache.')
                ->end()
            ->end()
            ->append($this->addMemcachedSection())
        ;

        return $treeBuilder;
    }

    /**
     *
     * @return ArrayNodeDefinition
     */
    private function addMemcachedSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('memcached_response');

        $node
            ->children()
                ->scalarNode('persistent_id')
                    ->defaultNull()
                    ->info('Specify to enable persistent connections. All clients with the same ID share connections.')
                ->end()
                ->arrayNode('hosts')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('dsn')->cannotBeEmpty()->isRequired()->end()
                            ->scalarNode('port')
                                ->cannotBeEmpty()
                                ->defaultValue(11211)
                                ->validate()
                                ->ifTrue(function ($v) { return !is_numeric($v); })
                                    ->thenInvalid('host port must be numeric')
                                ->end()
                            ->end()
                            ->scalarNode('weight')
                                ->defaultValue(0)
                                ->validate()
                                ->ifTrue(function ($v) { return !is_numeric($v); })
                                    ->thenInvalid('host weight must be numeric')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->append($this->addMemcachedOptionsSection())
            ->end()
        ->end();

        return $node;
    }

    /**
     * Configure the "lsw_memcache.options" section
     *
     * @return ArrayNodeDefinition
     */
    private function addMemcachedOptionsSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('options');

        // Memcached only configs
        $node
            ->children()
                ->booleanNode('compression')->defaultFalse()->end()
                ->scalarNode('serializer')
                    ->defaultValue('php')
                    ->validate()
                    ->ifNotInArray(array('php', 'json', 'igbinary'))
                        ->thenInvalid('serializer option must be: php, json or igbinary')
                    ->end()
                ->end()
                ->scalarNode('prefix_key')->defaultValue('')->end()
                ->scalarNode('hash')
                    ->defaultValue('default')
                    ->validate()
                    ->ifNotInArray(array('default', 'md5', 'crc', 'fnv1_64', 'fnv1a_64', 'fnv1_32', 'fnv1a_32', 'hsieh', 'murmur'))
                        ->thenInvalid('hash option must be: default, md5, crc, fnv1_64, fnv1a_64, fnv1_32, fnv1a_32, hsieh, murmur')
                    ->end()
                ->end()
                ->scalarNode('distribution')
                    ->defaultValue('modula')
                    ->validate()
                    ->ifNotInArray(array('modula', 'consistent'))
                        ->thenInvalid('distribution option must be: modula, consistent')
                    ->end()
                ->end()
                ->booleanNode('libketama_compatible')
                    ->info('Set this to true when using consistent hashing')
                    ->defaultFalse()
                ->end()
                ->booleanNode('buffer_writes')->defaultFalse()->end()
                ->booleanNode('binary_protocol')->defaultFalse()->end()
                ->booleanNode('no_block')->defaultFalse()->end()
                ->booleanNode('tcp_nodelay')->defaultFalse()->end()
                ->scalarNode('socket_send_size')
                    ->defaultValue(4096)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('socket_send_size option must be numeric')
                    ->end()
                ->end()
                ->scalarNode('socket_recv_size')
                    ->defaultValue(4096)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('socket_recv_size option must be numeric')
                    ->end()
                ->end()
                ->scalarNode('connect_timeout')
                    ->defaultValue(1000)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('connect_timeout option must be numeric')
                    ->end()
                ->end()
                ->scalarNode('retry_timeout')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('retry_timeout option must be numeric')
                    ->end()
                ->end()
                ->scalarNode('send_timeout')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('send_timeout option must be numeric')
                    ->end()
                ->end()
                ->scalarNode('recv_timeout')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('recv_timeout option must be numeric')
                    ->end()
                ->end()
                ->scalarNode('poll_timeout')
                    ->defaultValue(1000)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('poll_timeout option must be numeric')
                    ->end()
                ->end()
                ->booleanNode('cache_lookups')->defaultFalse()->end()
                ->scalarNode('server_failure_limit')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('server_failure_limit option must be numeric')
                    ->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }
}
