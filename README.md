WonderCacheBundle (UNDER CONSTRUCTION)
======================================

A wonder cache bundle for symfony 2. A full response cache with automatic invalidation.
Inspired by https://github.com/LeaseWeb/LswMemcacheBundle.


![screenshot](https://raw.github.com/lioshi/WonderCacheBundle/master/Resources/images/wondercache_workflow.png)


##config.yml

lioshi_wonder_cache:
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
### manual cache support TODO
if ($this->container->get('wonder.cache')->get($keyCacheName)){
    
    return $this->container->get('wonder.cache')->get($keyCacheName);

} else {
    
    $return = (...)

        $this->container->get('wonder.cache')->set(
            $content, 
            array(
                'Testa\ArticleBundle\Entity\Pack' => array(1,65,988), 
                'Testa\ArticleBundle\Entity\Export' => array(65,22)
            ),
            $cacheKeyName,
            'default',
            3600
        );

    return $return;
}

### object cache support TODO
Need configuration object.client

        $this->container->get('wonder.cache')->set(
            $content, 
            array(
                'Testa\ArticleBundle\Entity\Pack' => array(1,65,988), 
                'Testa\ArticleBundle\Entity\Export' => array(65,22)
            ),
            $cacheKeyName
        );





### response cache support (all page) DONE
Need configuration response.client

        $this->container->get('wonder.cache')->addLinkedEntities(
            array(
                'Testa\ArticleBundle\Entity\Pack' => array(1,65,988), 
                'Testa\ArticleBundle\Entity\Export' => array(65,22)
            )
        );
No cacheKeyName, the entire response is cached. 


##Credits

LSW/MemCahcebunfle...
