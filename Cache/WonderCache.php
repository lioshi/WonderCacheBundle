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
    private $duration;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->linkedEntities = array();
        $this->used = false;
        $this->duration = 0;
    }

    /**
     * Get the name of response's cache's key calculated with uri of response
     * 
     * @return string 
     */
    public function getResponseCacheKeyName($uri)
    {
        return 'wc_response_cache_'.md5($uri);
    }

    /**
     * Called by kernel.request event to set response from cache if exists
     * 
     * @param  GetResponseEvent $event 
     * @return null with updated response if cache exists                 
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->container->get('wonder.cache.logger')->addUri($event->getRequest()->getUri());

        if (!$this->container->getParameter('wondercache.activated')){
            $this->container->get('wonder.cache.logger')->addError('Wonder cache deactivated, see config.yml (set lioshi_wonder_cache.activated to true).');
            return; // deactivate the listenner action
        } 

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri().$this->getIncludedHeader($event));
        // echo $cacheKeyName."\n";

        if ($cacheContent = $this->container->get('memcached.response')->get($cacheKeyName)){
            
            $response = $cacheContent['content'];

            $response->headers->add(array('WC-Key' => $cacheKeyName ));

            $this->container->get('wonder.cache.logger')->addInfo('Response retrieved from cache [cache key: '.$cacheKeyName.']', $cacheContent['linkedEntities']);

            $event->setResponse($response);
            return; 
        } else {

            $this->container->get('wonder.cache.logger')->addWarning(
                array(
                    'Response missing from cache',
                    '- response cache is just saved and not yet used',
                    'or',
                    '- Wonder cache not run for this response'
                    )
                );
            return;
        }
    }

    /**
     * Called by kernel.response to save response's cache if needed
     * 
     * @param  FilterResponseEvent $event 
     * @return null if cache allready exists or response after saved it if no cache exists                      
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
file_put_contents('/tmp/gggg', "ok\n", FILE_APPEND);        
        if (!$this->container->getParameter('wondercache.activated')) return $event->getResponse(); // deactivate the listenner action

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri().$this->getIncludedHeader($event));
            
        if ($this->container->get('memcached.response')->get($cacheKeyName)){
            return;
        } else {

            $response = $event->getResponse();

            if ($this->getUsed()){
                $cacheContent = array();

                if ($this->getLinkedEntities()){
                    $cacheContent['linkedEntities'] = $this->getLinkedEntities();
                    $this->container->get('wonder.cache.logger')->addInfo('Response saved into cache [cache key: '.$cacheKeyName.'].', $this->getLinkedEntities());
                } else {
                    $this->container->get('wonder.cache.logger')->addWarning('Response saved into cache without entities linked [cache key: '.$cacheKeyName.'].');
                }
                
                $cacheContent['duration'] = $this->getDuration();
                $cacheContent['content'] = $response;
                $cacheContent['createdAt'] = microtime(true);

                $this->container->get('memcached.response')->set($cacheKeyName, $cacheContent, $this->getDuration());

            } else {
                $this->container->get('wonder.cache.logger')->addError(
                    array(
                        'Wonder cache not run for this response.',
                        'If you wish please use run() fonction.'
                        )
                    );
            }

            return $response;
        }
    }

    public function getLinkedEntities(){
        return $this->linkedEntities;
    }

    /**
     * Add new linked entities
     * 
     * @param array $entities to merge with existant entities
     */
    public function addLinkedEntities($entities){
        $this->linkedEntities = array_merge($this->linkedEntities, $entities);
        return $this;
    }

    public function addDuration($duration){
        $this->duration = $duration;
        return $this;
    }

    public function getDuration(){
        return $this->duration;
    }

    public function run($boolean = true){
        $this->used = $boolean;
        return $this;
    }

    public function getUsed(){
        return $this->used;
    }

    /**
    *  Get header content in function of config entrie wondercache.included_headers_keys
    *  
    *  Return serialized headers
    */        
    public function getIncludedHeader($event){

        $headers = $event->getRequest()->headers->all();
        
        if ($this->container->hasParameter('wondercache.included_headers_keys')){
            $headerKeysListToKeep = $this->container->getParameter('wondercache.included_headers_keys');
            $headersWithOnlyIncludedKey = array();
            foreach ($headerKeysListToKeep as $headerKey) {
                $headerKey = trim($headerKey);
                if($headerKey == 'ALL'){
                    $headersWithOnlyIncludedKey = $headers;
                    break;
                }
                if(array_key_exists($headerKey, $headers)){
                    $headersWithOnlyIncludedKey[$headerKey]=$headers[$headerKey];
                }
            }
            $headers = $headersWithOnlyIncludedKey;
        } 

        return serialize($headers);
    }

}
