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
     * Get the name of cache's key which store the linked entities and the related cache's keys
     * 
     * @return string 
     */
    public function getLinkedEntitiesToCachedKeysFilename() {
        return 'wc_linked_entities';
    }

    public function getDurationToCachedKeysFilename() {
        return 'wc_durations';
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

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri());
                    
        if ($this->container->get('memcached.response')->get($cacheKeyName)){
            $response = $this->container->get('memcached.response')->get($cacheKeyName);
            $response->headers->add(array('wc-response' => true ));

            $linkedEntities = $this->getLinkedEntitiesFromCachedKeys($cacheKeyName);
            $this->container->get('wonder.cache.logger')->addInfo('Response retrieved from cache [cache key: '.$cacheKeyName.']', $linkedEntities);

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
        if (!$this->container->getParameter('wondercache.activated')) return $event->getResponse(); // deactivate the listenner action

        $cacheKeyName = $this->getResponseCacheKeyName($event->getRequest()->getUri());
            
        if ($this->container->get('memcached.response')->get($cacheKeyName)){
            return;
        } else {

            $response = $event->getResponse();

            if ($this->getUsed()){
                $this->container->get('memcached.response')->set($cacheKeyName, $response, $this->getDuration());
                if ($this->getLinkedEntities()){
                    $this->addLinkedEntitiesToCachedKeys($cacheKeyName, $this->getLinkedEntities());
                    $this->container->get('wonder.cache.logger')->addInfo('Response saved into cache [cache key: '.$cacheKeyName.'].', $this->getLinkedEntities());
                } else {
                    $this->container->get('wonder.cache.logger')->addWarning('Response saved into cache without entities linked [cache key: '.$cacheKeyName.'].');
                }
                $this->addDurationToCachedKeys($cacheKeyName, $this->getDuration());
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

    /**
     * Add new linked entities
     * 
     * @param array $entities to merge with existant entities
     */
    public function addLinkedEntities($entities){
        $this->linkedEntities = array_merge($this->linkedEntities, $entities);
        return $this;
    }

    public function getLinkedEntities(){
        return $this->linkedEntities;
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
     * Add linked entities to existant cache's key which stores relation between cache and entities
     * 
     * @param string $key      the cache's key
     * @param array $entities  the entities linked
     */
    public function addLinkedEntitiesToCachedKeys($key, $entities){

        if (is_array($entities) && count($entities)){
        
            $linkedEntitiesToCachedKeysFile = $this->getLinkedEntitiesToCachedKeysFilename();    

            foreach ($entities as $linkedModel => $entitiesIds) {
                $entities[$linkedModel] = array();
                $entities[$linkedModel][$key] = $entitiesIds;
            }

            if ($this->container->get('memcached.response')->get($linkedEntitiesToCachedKeysFile)){
                $linkedEntitiesToCachedKeysFileContent = $this->container->get('memcached.response')->get($linkedEntitiesToCachedKeysFile);
                $entities = array_merge_recursive($linkedEntitiesToCachedKeysFileContent,$entities);
            } 
            
            $this->container->get('memcached.response')->set($linkedEntitiesToCachedKeysFile, $entities,0); 
        }
    }

    public function addDurationToCachedKeys($key, $duration=0){

        
            $durationsToCachedKeysFile = $this->getDurationToCachedKeysFilename();    

            if ($this->container->get('memcached.response')->get($durationsToCachedKeysFile)){
                $durations = $this->container->get('memcached.response')->get($durationsToCachedKeysFile);
                $durations[$key] = $duration;
            } else {
                $durations = array();
                $durations[$key] = $duration;
            }
            
            $this->container->get('memcached.response')->set($durationsToCachedKeysFile, $durations,0); 
    }


    /**
     * Get linked entities for a cache's key
     * 
     * @param  string $key     the cache's key
     * @return array $entities linked to the cache's key
     */
    public function getLinkedEntitiesFromCachedKeys($key){

        $entities = array();
        $linkedEntitiesToCachedKeysFile = $this->getLinkedEntitiesToCachedKeysFilename();    

        if ($this->container->get('memcached.response')->get($linkedEntitiesToCachedKeysFile)){
            $linkedEntitiesToCachedKeysFileContent = $this->container->get('memcached.response')->get($linkedEntitiesToCachedKeysFile);
                
            foreach ($linkedEntitiesToCachedKeysFileContent as $entity => $cacheInfos) {
                foreach ($cacheInfos as $cacheKey => $ids) {
                     if ($cacheKey == $key){
                        $entities[$entity] = $ids;
                     }
                } 
            }
        } 
            
        return $entities;
    }

}
