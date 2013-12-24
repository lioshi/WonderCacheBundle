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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->responseClient = false;
        $this->objectClient = false;
        if ($container->hasParameter('wondercache.response.client')){
            $this->responseClient = $container->getParameter('wondercache.response.client');
        }
        if ($container->hasParameter('wondercache.object.client')){
            $this->objectClient = $container->getParameter('wondercache.object.client');
        }
    }

    public function onKernelRequest(GetResponseEvent $event)
    {

// return; // do deactivate the listenner action

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri());
                    
        if ($this->container->get('memcache.'.$this->responseClient)->get($cacheKeyName)){
            $response = $this->container->get('memcache.'.$this->responseClient)->get($cacheKeyName);
            $response->headers->add(array('response-cache' => true ));
            $event->setResponse($response);
            return; 
        } else {
            return;
        }
    }


    public function onKernelResponse(PostResponseEvent $event)
    {
        
// return $event->getResponse(); // do deactivate the listenner action

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri());
            
        if ($this->container->get('memcache.'.$this->responseClient)->get($cacheKeyName)){
                
            // purge session of linkedEntities allreadymanaged
            $this->container->get('session')->set('linked-entities', '');                
            return;

        } else {

            $response = $event->getResponse();

            // put this in page candidate to FPC
            // $response->headers->add(array('linked-entities' => '{sqdsqdsqdsqd}'));

            // save to memcached if response content has {entityModelLinks}
            $contentTypesAllowedInCache = array('application/json', 'text/html');
                

// $response->headers->add(array('Linked-entities' => 'xxxxxxxxx 8 xxxxxxxxxx' ));                
// var_dump($response->headers->keys());


            $linkedEntities = $this->container->get('session')->get('linked-entities', false);



// if ($linkedEntities){ 
// $fp = fopen("/data/www/testa/web/logInvalidatorCache.txt","w"); 
// $cachelogs  = '0: '.$cacheKeyName;
// $cachelogs .= '1: '.print_r($response->headers->keys(), true);
// $cachelogs .= '2: '.print_r($event->getResponse()->headers->keys(), true);
// $cachelogs .= 'content-type: '.$event->getResponse()->headers->get('content-type');
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

                $this->container->get('memcache.'.$this->responseClient)->set($cacheKeyName, $response, 0, $linkedEntities);
            }

            // purge session of linkedEntities allreadymanaged
            $this->container->get('session')->set('linked-entities', '');
            return $response;

        }
        

    }

    public function getResponseCacheKeyName($uri)
    {
        return '1_cache_response_'.$uri;
        // return 'response_'.md5($uri);
    }

    public function addLinkedEntities($entities){
        
        // TODO: merge new entites with existant


        $this->container->get('session')->set('linked-entities', $entities);

    }



    public function set($content, $linkedEntities, $cacheKeyName = false, $client = false,  $ttl = 0){
        
        if ($cacheKeyName && !$client){ // object cache
            
            if ($this->objectClient){
                $this->container->get('memcache.'.$this->objectClient)->set($cacheKeyName, $content, $ttl, $linkedEntities);
            }

        } elseif (!$cacheKeyName && !$client) { // response cache

            // put linkedEntities in session
            $this->addLinkedEntities($entities);
            // and cache is set with $this->onKernelResponse launch by event kernel.terminate

        } elseif ($cacheKeyName && $client) { // manual cache

            $this->container->get('memcache.'.$client)->set($cacheKeyName, $content, $ttl, $linkedEntities);

        }
        

    }

    public function get($cacheKeyName, $client = false){
        
        if ($cacheKeyName && !$client){ // object cache
            
            if ($this->objectClient){
                $this->container->get('memcache.'.$this->objectClient)->get($cacheKeyName);
            }

        } elseif (!$cacheKeyName && !$client) { // response cache

            // no get for this cache, event kernel.terminate manage this

        } elseif ($cacheKeyName && $client) { // manual cache

            $this->container->get('memcache.'.$client)->get($cacheKeyName);

        }


    }


}
