WonderCacheBundle
=================

A wonder cache bundle for symfony 2. A full response cache with automatic invalidation.
Inspired by https://github.com/LeaseWeb/LswMemcacheBundle.


##config.yml

lioshi_wonder_cache:
    clients:
        default:
            hosts: 
                - { dsn: localhost, port: 11211 }
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
    response:
        client: default    # client for response cache
    object:
        client: default    # client for object cache


##Usage
### manual cache support
if ($this->container->get('memcache.default')->get($keyCacheName)){
    
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

### object cache support 
Need configuration object.client

        $this->container->get('wonder.cache')->set(
            $content, 
            array(
                'Testa\ArticleBundle\Entity\Pack' => array(1,65,988), 
                'Testa\ArticleBundle\Entity\Export' => array(65,22)
            ),
            $cacheKeyName
        );

### response cache support (all page)
Need configuration response.client

        $this->container->get('wonder.cache')->set(
            $content, 
            array(
                'Testa\ArticleBundle\Entity\Pack' => array(1,65,988), 
                'Testa\ArticleBundle\Entity\Export' => array(65,22)
            )
        );
No cacheKeyName, the entire response is cached. 
