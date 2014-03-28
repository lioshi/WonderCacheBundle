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

        $WonderCache = new WonderCache($this->container);
        $memcached = $this->container->get('memcached.response'); 

        $LinkedModelsToCachedKeys = $memcached->get($WonderCache->getLinkedEntitiesToCachedKeysFilename());

        foreach ($classesToDelete as $classToDelete => $idsFlush) {
            $warning = array();
            $warning[] = 'Cache invalidation processed';
            $warning[] = 'Doctrine just updated/deleted or inserted entity : '.$classToDelete;
            $warning[] = $classToDelete.'\'s ids concerned : '.implode(', ',$idsFlush);

            if (isset($LinkedModelsToCachedKeys[$classToDelete])){
                foreach ($LinkedModelsToCachedKeys[$classToDelete] as $key => $entitiesIds) {
                    // delete cache ley if idsFlush are in entities linked to cache key OR if all entities are linked to the cache (ie: $entitiesIds is an empty array)
                    if (count(array_intersect($entitiesIds, $idsFlush)) || !count($entitiesIds)){
                        $memcached->delete($key);
                        if (count($entitiesIds)){
                            $nbIds = count(array_intersect($entitiesIds, $idsFlush));
                            $idsDetails = '('.implode(',',array_intersect($entitiesIds, $idsFlush)).')';
                        } else {
                            $nbIds = 'ALL';
                            $idsDetails = '';
                        }
                        $warning[] = 'Cache key deleted : '.$key. ' cause '.$nbIds.' '.$classToDelete.'\'s id was linked '.$idsDetails;


                        // deleted entry in memcached saved entities linked
                        unset($LinkedModelsToCachedKeys[$classToDelete][$key]);
                        $memcached->set($WonderCache->getLinkedEntitiesToCachedKeysFilename(), $LinkedModelsToCachedKeys, 0);
                    } 
                }
            }
            $this->container->get('wonder.cache.logger')->addWarning($warning);

        }
        return;
    }
}
