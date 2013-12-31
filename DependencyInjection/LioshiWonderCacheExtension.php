<?php

namespace Lioshi\WonderCacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Applies the configuration for the Memcached object
 * Based on Lsw\MemcacheBundle by LeaseWeb
 */
class LioshiWonderCacheExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.yml');
        $loader->load('services.yml');

        // Parameter used in services launched by kernel event
        if (isset($config['activated'])) {
            $container->setParameter('wondercache.activated', $config['activated']);
        } else {
            $container->setParameter('wondercache.activated', false);
        }

        // Add servers to the parameters for declaration of memcached client in services.yml
        $servers = array();
        foreach ($config['memcached_response']['hosts'] as $host) {
            $servers[] = array(
                'dsn' => $host['dsn'],
                'port' => $host['port'],
                'weight' => $host['weight']
            );
        }
        $container->setParameter('memcached.servers', $servers);  

        // Add override options to the parameters
        // Default options are in memcached.default_options in config.yml
        if (isset($config['memcached_response']['options'])) {
            $container->setParameter('memcached.override_options', $config['memcached_response']['options']);
        } else {
            $container->setParameter('memcached.override_options', array());
        }
    }


}
