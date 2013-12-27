<?php

namespace Lioshi\WonderCacheBundle\Cache;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class WonderCache
{
    private $container;
    private $linkedEntities;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->linkedEntities = array();
        $this->used = false;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->container->getParameter('wondercache.activated')) return; // deactivate the listenner action

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri());
                    
        if ($this->container->get('memcached.response')->get($cacheKeyName)){
            $response = $this->container->get('memcached.response')->get($cacheKeyName);
            $response->headers->add(array('wc-response' => true ));
            // info of entities linked to response cache
            // TODO: add webdebug bar info
            
            $event->setResponse($response);
            return; 
        } else {
            return;
        }
    }

    public function onKernelResponse(PostResponseEvent $event)
    {
        
        if (!$this->container->getParameter('wondercache.activated')) return $event->getResponse(); // deactivate the listenner action

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri());
            
        if ($this->container->get('memcached.response')->get($cacheKeyName)){
            return;
        } else {

            $response = $event->getResponse();

            if ($this->getUsed()){
                $this->container->get('memcached.response')->set($cacheKeyName, $response, 0);
                if ($this->getLinkedEntities()){
                    $this->addLinkedEntitiesToCachedKeys($cacheKeyName, $this->getLinkedEntities(), 'response');
                    // TODO: add webdebug bar info
                }
            }

            return $response;
        }
    }

    public function getResponseCacheKeyName($uri)
    {
        return 'wc_response_cache_'.$uri;
        // return 'response_'.md5($uri);
    }

    public function addLinkedEntities($entities){
        $this->linkedEntities = array_merge($this->linkedEntities, $entities);
        return $this;
    }

    public function getLinkedEntities(){
        return $this->linkedEntities;
    }

    public function run($boolean = true){
        $this->used = $boolean;
        return $this;
    }

    public function getUsed(){
        return $this->used;
    }

    public function getLinkedEntitiesToCachedKeysFilename() {
        return 'wc_linked_entities';
    }

    public function addLinkedEntitiesToCachedKeys($key, $entities, $client){

        if (is_array($entities) && count($entities) && $client){
        
            $linkedEntitiesToCachedKeysFile = $this->getLinkedEntitiesToCachedKeysFilename();    

            foreach ($entities as $linkedModel => $entitiesIds) {
                $entities[$linkedModel] = array();
                $entities[$linkedModel][$key] = $entitiesIds;
            }

            if ($this->container->get('memcached.'.$client)->get($linkedEntitiesToCachedKeysFile)){
                $linkedEntitiesToCachedKeysFileContent = $this->container->get('memcached.'.$client)->get($linkedEntitiesToCachedKeysFile);
                $entities = array_merge_recursive($linkedEntitiesToCachedKeysFileContent,$entities);
            } 
            
            $this->container->get('memcached.'.$client)->set($linkedEntitiesToCachedKeysFile, $entities,0); 
        }
    }

    // TODO: manage data's cache
    // public function set($content, $linkedEntities, $cacheKeyName = false, $client = false,  $ttl = 0){
    //     if ($cacheKeyName && !$client){ // object cache
    //         $this->container->get('memcache.'.$this->objectClient)->set($cacheKeyName, $content, $ttl);
    //         //  manage  $linkedEntities 
    //     } elseif (!$content && !$cacheKeyName && !$client) { // response cache
    //         // save linkedEntities
    //         $this->addLinkedEntities($linkedEntities);
    //         // and cache is set with $this->onKernelResponse launch by event kernel.terminate
    //     } elseif ($cacheKeyName && $client) { // manual cache
    //         $this->container->get('memcache.'.$client)->set($cacheKeyName, $content, $ttl);
    //         // manage $linkedEntities
    //     }
    // }
    // public function get($cacheKeyName, $client = false){
    //     if ($cacheKeyName && !$client){ // object cache
    //         $this->container->get('memcache.'.$this->objectClient)->get($cacheKeyName);
    //     } elseif (!$cacheKeyName && !$client) { // response cache
    //         // no get for this cache, event kernel.terminate manage this
    //     } elseif ($cacheKeyName && $client) { // manual cache
    //         $this->container->get('memcache.'.$client)->get($cacheKeyName);
    //     }
    // }

}
