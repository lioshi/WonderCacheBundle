<?php

namespace Lioshi\WonderCacheBundle\Cache;

use Doctrine\ORM\Event\OnFlushEventArgs;
use \Exception;

/**
 * Class used to invalidate cache for responses which have entites linked.
 * When an entity is updated, inserted or deleted the onFlush function get the response's caches linked and delete.
 * 
 */
class CacheInvalidator
{
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $dateFlush = microtime(true);
        
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        $scheduledEntityChanges = array(
            'insert' => $uow->getScheduledEntityInsertions(),
            'update' => $uow->getScheduledEntityUpdates(),
            'delete' => $uow->getScheduledEntityDeletions()
        );

        $classesToDelete = array();

        foreach ($scheduledEntityChanges as $change => $entities) {
            $idsFlush = array();
            foreach($entities as $entity) {
                if (method_exists($entity, 'getId')){
                    $idsFlush[] = $entity->getId();
                }
                if (array_key_exists(get_class($entity), $classesToDelete)){
                    $a = $classesToDelete[get_class($entity)];
                } else {
                    $a = array();
                }
                $classesToDelete[get_class($entity)] = array_merge($idsFlush, $a);
            }
        }
      
        // add log to see invalidations
        $infos = date("Y-m-d H:i:s")."\n";
        foreach ($classesToDelete as $class => $value) {
            $infos = '';
            $infos .= "Cache invalidation processed\n";
            $infos .= "Doctrine just updated/deleted or inserted entity : ".$class."\n";
            $infos .= $class.'\'s ids concerned : '.implode(', ',$value)."\n";
        }

        $memcached = $this->container->get('memcached.response');

        $nbCacheKeyConcerned = 0;

        foreach ($memcached->getAllKeys() as $key => $displayKey) {

            $contentCachedKey = $memcached->get($key);

            foreach($contentCachedKey['linkedEntities'] as $entity => $entityIds){

                if(array_key_exists($entity, $classesToDelete)){
                    if ((count(array_intersect($entityIds,  $classesToDelete[$entity])) || !count($entityIds)) // if an id match, or all ids
                        && $contentCachedKey['createdAt'] < $dateFlush // if date flush posterior of createdDate of entry
                        ){ 
                        $nbCacheKeyConcerned++;
                        $memcached->delete($key); 
                        if (count($entityIds)){
                            $nbIds = count(array_intersect($entityIds, $classesToDelete[$entity]));
                            $idsDetails = '('.implode(',',array_intersect($entityIds, $classesToDelete[$entity])).')';
                        } else {
                            $nbIds = 'ALL';
                            $idsDetails = '';
                        }
                        $infos .= 'Cache key deleted at '.date("Y-m-d H:i:s").' : '.$key. ' cause '.$nbIds.' '.$entity.'\'s id was linked '.$idsDetails.' ['.date("Y-m-d H:i:s", $contentCachedKey['createdAt']).' < '.date("Y-m-d H:i:s", $dateFlush).']';
                        $infos .= "\n";
                    }
                }
            }
        }

        if(!$nbCacheKeyConcerned){
            $infos .= 'No cache key deleted';
        }

        $infos .= "\n";

        if(is_file('/tmp/testaInvalidationCache.log')){
                // log roulant
                if(filesize('/tmp/testaInvalidationCache.log') < 1000000){
                    $infos = file_get_contents('/tmp/testaInvalidationCache.log').$infos;
                }

                file_put_contents('/tmp/testaInvalidationCache.log', $infos);
        }

        return;
    }
}
                                         