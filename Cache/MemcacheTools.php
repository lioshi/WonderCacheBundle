<?php

namespace Lioshi\WonderCacheBundle\Cache;

use Doctrine\ORM\Event\OnFlushEventArgs;
use \Exception;

class MemcacheTools
{
  
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * get all keys from memecahced servers hosts in parameters
     * @return [type] [description]
     */
    public function getMemcacheKeys($client)
    {
        return $this->getMemCachedByClient($client)->getAllKeys();
    } 

    /**
     * get memcached object
     * @param  string $client  if not specified then all client
     * @return [type]          [description]
     */
    public function getMemCachedByClient($client)
    {
        if ($client){
            $paramMemcachehosts = $this->container->getParameter('wondercache.memcached.'.$client);  // get parameters hosts for memcached 
            foreach ($paramMemcachehosts['hosts'] as $host) {
                $servers[] = array($host['dsn'],$host['port']);
            }
        } 

        $memcache = new \Memcached;
        $memcache->addServers($servers); // connect to those servers

        return $memcache;
    }

 
    
}
