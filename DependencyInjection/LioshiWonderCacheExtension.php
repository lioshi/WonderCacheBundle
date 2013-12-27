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

        if (isset($config['memcached_response'])) {
            $this->newMemcachedClient('response', $config['memcached_response'], $container);
            // $container->setParameter('wondercache.memcached.clients', $config['clients']);
        }


    }

    private function enableResponseSupport($config, ContainerBuilder $container)
    {
        // make sure the client is specified and it exists
        $client = $config['response']['client'];
        if (null === $client) {
            return;
        }
        if (!isset($config['clients']) || !isset($config['clients'][$client])) {
            throw new \LogicException(sprintf('The client "%s" does not exist! Cannot enable the response support!', $client));
        }
        
        $container->setParameter('wondercache.response.client', $client);
    }

    private function enableObjectSupport($config, ContainerBuilder $container)
    {
        // make sure the client is specified and it exists
        $client = $config['object']['client'];
        if (null === $client) {
            return;
        }
        if (!isset($config['clients']) || !isset($config['clients'][$client])) {
            throw new \LogicException(sprintf('The client "%s" does not exist! Cannot enable the object support!', $client));
        }
        
        $container->setParameter('wondercache.object.client', $client);
    }

    /**
     * Creates a new Memcached definition
     *
     * @param string           $name      Client name
     * @param array            $config    Client configuration
     * @param ContainerBuilder $container Service container
     *
     * @throws \LogicException
     */
    private function newMemcachedClient($name, array $config, ContainerBuilder $container)
    {
        // Check if the Memcached extension is loaded
        if (!extension_loaded('memcached')) {
            throw new \LogicException('Memcached extension is not loaded! To configure memcached clients it MUST be loaded!');
        }

        $memcached = new Definition('Lioshi\WonderCacheBundle\Cache\Memcache');

        // Check if it has to be persistent
        if (isset($config['persistent_id'])) {
            $memcached->addArgument($config['persistent_id']);
        }

        // Add servers to the memcached client
        $servers = array();
        foreach ($config['hosts'] as $host) {
            $servers[] = array(
                $host['dsn'],
                $host['port'],
                $host['weight']
            );
        }
        $memcached->addMethodCall('addServers', array($servers));

        // Get default memcached options
        $options = $container->getParameter('memcache.default_options');

        // Add overriden options
        if (isset($config['options'])) {
            foreach ($options as $key => $value) {
                if (isset($config['options'][$key])) {
                    if ($key == 'serializer') {
                        // serializer option needs to be supported and is a constant
                        if ($value != 'php' && !constant('Memcached::HAVE_' . strtoupper($value))) {
                            throw new \LogicException("Invalid serializer specified for Memcached: $value");
                        }
                        $newValue = constant('Memcached::SERIALIZER_' . strtoupper($value));
                    } elseif ($key == 'distribution') {
                        // distribution is defined as a constant
                        $newValue = constant('Memcached::DISTRIBUTION_' . strtoupper($value));
                    } else {
                        $newValue = $config['options'][$key];
                    }
                    if ($config['options'][$key]!=$value) {
                        // not default, add method call and update options
                        $constant = 'Memcached::OPT_'.strtoupper($key);
                        $memcached->addMethodCall('setOption', array(constant($constant), $newValue));
                        $options[$key] = $newValue;
                        // add prefix in container param
                        if ($key == 'prefix_key')
                            $container->setParameter('wondercache.memcached.'.$name.'.prefix', $config['options'][$key]);
                    }

                }
            }
        }

        // Make sure that config values are human readable
        foreach ($options as $key => $value) {
            $options[$key] = var_export($value, true);
        }

        // Add the service to the container
        $serviceName = sprintf('memcache.%s', $name);
        $container->setDefinition($serviceName, $memcached);

    }

}
