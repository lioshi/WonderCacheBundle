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
        $this->responseClient = false;
        $this->objectClient = false;
        if ($container->hasParameter('wondercache.response.client')){
            $this->responseClient = $container->getParameter('wondercache.response.client');
        }
        if ($container->hasParameter('wondercache.object.client')){
            $this->objectClient = $container->getParameter('wondercache.object.client');
        }
        if ($container->hasParameter('wondercache.memcached.response.prefix')){
            $this->objectClient = $container->getParameter('wondercache.object.client');
        }


    }




    public function onKernelRequest(GetResponseEvent $event)
    {

        if (!$this->container->getParameter('wondercache.activated')) return; // deactivate the listenner action

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri());
                    
        if ($this->container->get('memcache.'.$this->responseClient)->get($cacheKeyName)){
            $response = $this->container->get('memcache.'.$this->responseClient)->get($cacheKeyName);
            
            // info of response cache used
            $response->headers->add(array('wc-response-cache' => true ));
            // info of entities linked to response cache
            // TODO
            
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
            
        if ($this->container->get('memcache.'.$this->responseClient)->get($cacheKeyName)){
            return;
        } else {

            $response = $event->getResponse();

            // put this in page candidate to FPC
            // $response->headers->add(array('linked-entities' => '{sqdsqdsqdsqd}'));

            // save to memcached if response content has {entityModelLinks}
            $contentTypesAllowedInCache = array('application/json', 'text/html');
                

// $response->headers->add(array('Linked-entities' => 'xxxxxxxxx 8 xxxxxxxxxx' ));                
// var_dump($response->headers->keys());


            $linkedEntities = $this->getLinkedEntities();
// if ($linkedEntities){ 
// $fp = fopen("/data/www/testa/web/logInvalidatorCache.txt","w"); 
// $cachelogs  = '0: '.$cacheKeyName;
// $cachelogs .= '1: '.print_r($linkedEntities, true);
// $cachelogs .= '2: '.print_r($response->headers->keys(), true);
// $cachelogs .= 'content-type: '.$response->headers->get('content-type');
// $cachelogs .= '------------';
// $cachelogs .= "\n";
// fputs($fp, $cachelogs); 
// }
            $validContentType = false;
            foreach ($contentTypesAllowedInCache as $contentTypeAllowedInCache) {
                if(strpos($response->headers->get('content-type'), $contentTypeAllowedInCache)!== false){
                    $validContentType = true;
                    break;
                }
            }
            if ($validContentType && $linkedEntities ){

                $this->container->get('memcache.'.$this->responseClient)->set($cacheKeyName, $response, 0);
                // manage $linkedEntities
                $this->addLinkedEntitiesToCachedKeys($cacheKeyName, $linkedEntities, $this->responseClient);
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
        
        // merge new entites with existant
        $this->linkedEntities = array_merge($this->linkedEntities, $entities);
    }

    public function getLinkedEntities(){
        return $this->linkedEntities;
    }

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

    public function getLinkedEntitiesToCachedKeysFilename() {
        return 'wc_linked_entities';
    }

    public function addLinkedEntitiesToCachedKeys($key, $entities, $client){

    // if(in_array($this->get('kernel')->getEnvironment(), array('prod'))) {
                
        if (is_array($entities) && count($entities) && $client){
        
            $linkedEntitiesToCachedKeysFile = $this->getLinkedEntitiesToCachedKeysFilename();    

            // array of models with key value
            // $entities = array_flip($entities);
            foreach ($entities as $linkedModel => $entitiesIds) {
                $entities[$linkedModel] = array();
                $entities[$linkedModel][$key] = $entitiesIds;
            }

            if ($this->container->get('memcache.'.$client)->get($linkedEntitiesToCachedKeysFile)){

                $linkedEntitiesToCachedKeysFileContent = $this->container->get('memcache.'.$client)->get($linkedEntitiesToCachedKeysFile);

                $entities = array_merge_recursive($linkedEntitiesToCachedKeysFileContent,$entities);
            } 
            
            $this->container->get('memcache.'.$client)->set($linkedEntitiesToCachedKeysFile, $entities,0); 

            
        }
    // }
    }

}
