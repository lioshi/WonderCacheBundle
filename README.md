![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/icon_.png) WonderCacheBundle 
=================
A full response cache with automatic invalidation via Doctrine events:
- just one service's call to manage cache of an action
- no wasted time setting up a cache invalidation system

[![knpbundles.com](http://knpbundles.com/lioshi/WonderCacheBundle/badge)](http://knpbundles.com/lioshi/WonderCacheBundle)

[![knpbundles.com](http://knpbundles.com/lioshi/WonderCacheBundle/badge-short)](http://knpbundles.com/lioshi/WonderCacheBundle)

## How works **WonderCache**
![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/wondercache_workflow.png)

**WonderCache** is there at request and bypasses all framework. As a proxy. 
But WonderCache knows when invalidate its cache.

## Requirements
- PHP 5.3.x or more
- php5-memcached 2.x

## Installation
To install WonderCacheBundle with Composer just add the following to your _composer.json_ file:

    {
        require: {
            "lioshi/wonder-cache-bundle": "dev-master",
            ...
        }
    }

The next thing you should do is install the bundle by executing the following command:

    php composer.phar update lioshi/wonder-cache-bundle

Finally, add the bundle to the registerBundles function of the AppKernel class in the _app/AppKernel.php_ file:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Lioshi\WonderCacheBundle\LioshiWonderCacheBundle(),
            ...
        );

Configure the bundle by adding the following to _app/config/config.yml_:

```yml
    lioshi_wonder_cache:
        activated: true
        memcached_response:
            hosts: 
                - { dsn: localhost, port: 11211 }
```

### Dependencies
>in Debian based systems
    
    apt-get install memcached php5-memcached

>in Centos based systems
   
    yum install php-pecl-memcached 

Do not forget to restart you web server after adding the Memcache module. 

## Commands
The ```wondercache:clear``` command delete all cached items and ```wondercache:list``` command can list all cache's keys and can display content of a choosen key.

## Full configuration
```yml
    lioshi_wonder_cache:
        activated: true
        memcached_response:
            hosts: 
                - { dsn: localhost, port: 11211, weight: 60 }
                - { dsn: localhost, port: 11212, weight: 30 }
            options:
                compression: true
                serializer: 'json'
                prefix_key: ""
                hash: default
                distribution: 'consistent'
                libketama_compatible: true
                buffer_writes: true
                binary_protocol: true
                no_block: true
                tcp_nodelay: false
                socket_send_size: 4096
                socket_recv_size: 4096
                connect_timeout: 1000
                retry_timeout: 0
                send_timeout: 0
                recv_timeout: 0
                poll_timeout: 1000
                cache_lookups: false
                server_failure_limit: 0
```

## Usage
Into a controller you can run() **WonderCache** and specified optionnaly entities which are linked to the controller response.
The following exemple means that the controller's response depends on (or is linked to):
- 3 packs with ids 1, 65 and 988
- 2 exports with ids 65 and 22
- all cars

Exemple's code for a controller:

        $this->container->get('wonder.cache')
            ->run()
            ->addLinkedEntities(
            array(
                'Me\MyBundle\Entity\Pack' => array(1,65,988), 
                'Me\MyBundle\Entity\Export' => array(65,22),
                'Me\MyBundle\Entity\Cars' => array()
            )
        );

## Profiler's informations
> With symfony toolbar you can follow how **WonderCache** performs. 
> If there's some error or warning:

![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/wondercache_toolbar_errors.png)

>If all is good...

![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/wondercache_toolbar_infos.png)

>... you can see more informations about how **WonderCache** save your time:

![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/wondercache_profiler_infos.png)

## Credits
Inspired by https://github.com/LeaseWeb/LswMemcacheBundle:
- DependencyInjection/Configuration.php
- Command/ClearCommand.php

