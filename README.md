WonderCacheBundle
=================

A wonder cache bundle for symfony 2. A full response cache with automatic invalidation.
Inspired by https://github.com/LeaseWeb/LswMemcacheBundle.


##config.yml

lioshi_wondercache:
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
