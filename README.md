WonderCacheBundle 
=================

__Unstable UNDER CONSTRUCTION__
===============================

A wonder cache bundle for symfony 2. A full response cache with automatic invalidation via Doctrine event.

![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/wondercache_workflow.png)

If you want to optimize your web application for high load and/or low load times Memcache is an indispensable tool.
It will manage your session data without doing disk I/O on web or database servers. You can also run it as a
central object storage for your website. In this role it is used for caching database queries using the Doctrine 
caching support or expensive API calls by implementing the caching using Memcache "get" and "set" commands.

This Symfony2 bundle will provide Memcache integration into Symfony2 and Doctrine for session storage and caching. 
It has full Web Debug Toolbar integration that allows you to analyze and debug the cache behavior and performance.


####Commands
The ```wondercache:clear``` can delete all memcached keys and all memcached keys with prefix too
The ```wondercache:list``` can list all memcached keys and can display a key

---

### Requirements

- PHP 5.3.x or 5.4.x
- php5-memcached 1.x or 2.x (this is the PHP "memcached" extension that uses "libmemcached")

NB: Unlike the PHP "memcache" extension, the PHP "memcached" extension is not (yet) included in the PHP Windows binaries.

### Installation

To install LswMemcacheBundle with Composer just add the following to your 'composer.json' file:

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/lioshi/LswMemcacheBundle.git"
        }
    ],

(...)

    {
        require: {
            "lioshi/memcache-bundle": "*",
            ...
        }
    }

The next thing you should do is install the bundle by executing the following command:

    php composer.phar update leaseweb/memcache-bundle

Finally, add the bundle to the registerBundles function of the AppKernel class in the 'app/AppKernel.php' file:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Lsw\MemcacheBundle\LswMemcacheBundle(),
            ...
        );

Configure the bundle by adding the following to app/config/config.yml':

```yml
lsw_memcache:
    clients:
        default:
            hosts:
              - { dsn: localhost, port: 11211 }
```

Install the following dependencies (in Debian based systems using 'apt'):

    apt-get install memcached php5-memcached

Do not forget to restart you web server after adding the Memcache module. Now the Memcache
information should show up with a little double arrow (fast-forward) icon in your debug toolbar.














##config.yml
    lioshi_wonder_cache:
        activated: true
        memcached_response:
            hosts: 
                - { dsn: localhost, port: 11211, weight: 100 }
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

##Usage
### response cache support  
        $this->container->get('wonder.cache')
            ->run()
            ->addLinkedEntities(
            array(
                'Testa\ArticleBundle\Entity\Pack' => array(1,65,988), 
                'Testa\ArticleBundle\Entity\Export' => array(65,22)
            )
        );

##Credits
Inspired by https://github.com/LeaseWeb/LswMemcacheBundle:
- DependencyInjection/Configuration.php
- DependencyInjection/LswMemcacheExtension.php
- Command/ClearCommand.php

