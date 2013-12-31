WonderCacheBundle 
=================
A wonder cache bundle for symfony 2.  
A full response cache with automatic invalidation via Doctrine event.

![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/wondercache_workflow.png)

---

## Requirements

- PHP 5.3.x or 5.4.x
- php5-memcached 2.x

## Installation

To install WonderCacheBundle with Composer just add the following to your 'composer.json' file:

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/lioshi/WonderCacheBundle.git"
        }
    ],
NB: _not yet in packagist_

(...)

    {
        require: {
            "lioshi/wonder-cache-bundle": "dev-master",
            ...
        }
    }

The next thing you should do is install the bundle by executing the following command:

    php composer.phar update lioshi/wonder-cache-bundle

Finally, add the bundle to the registerBundles function of the AppKernel class in the 'app/AppKernel.php' file:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Lioshi\WonderCacheBundle\LioshiWonderCacheBundle(),
            ...
        );

Configure the bundle by adding the following to app/config/config.yml':

```yml
    lioshi_wonder_cache:
        activated: true
        memcached_response:
            hosts: 
                - { dsn: localhost, port: 11211 }
```

Install the following dependencies:

(in Debian based systems)
    
    apt-get install memcached php5-memcached

(in Centos based systems)
   
    yum install php-pecl-memcached 

Do not forget to restart you web server after adding the Memcache module. 


## Commands
The ```wondercache:clear``` command deletes all memcached's items and ```wondercache:list``` command can list all memcached's keys and can display content of a key.

## Full configuration (config.yml)
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
Into a controller you can run() wonderCache and specified optionnaly entities which arer linked to the controller response.
The following exemple means that the controller's response depends on (or is linked to):
- 3 packs
- 2 exports
- all cars

Exemple's code for a controller

        $this->container->get('wonder.cache')
            ->run()
            ->addLinkedEntities(
            array(
                'Me\MyBundle\Entity\Pack' => array(1,65,988), 
                'Me\MyBundle\Entity\Export' => array(65,22),
                'Me\MyBundle\Entity\Cars' => array()
            )
        );

## Credits
Inspired by https://github.com/LeaseWeb/LswMemcacheBundle:
- DependencyInjection/Configuration.php
- DependencyInjection/LswMemcacheExtension.php
- Command/ClearCommand.php

