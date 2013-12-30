<?php

namespace Lioshi\WonderCacheBundle\Cache;

use Doctrine\ORM\Event\OnFlushEventArgs;

use \Exception;

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
        $MemcacheTools = new MemcacheTools($this->container);
        $memcached = $MemcacheTools->getMemCachedByClient('response'); 

        $LinkedModelsToCachedKeys = $memcached->get($WonderCache->getLinkedEntitiesToCachedKeysFilename());

        foreach ($classesToDelete as $classToDelete => $idsFlush) {

            $warning = array();
            $warning[] = 'Cache invalidation processed';
            $warning[] = 'Doctrine just updated/deleted or inserted entity : '.$classToDelete;
            $warning[] = 'Entity\'s ids concerned : '.implode(', ',$idsFlush);

            if (isset($LinkedModelsToCachedKeys[$classToDelete])){
                foreach ($LinkedModelsToCachedKeys[$classToDelete] as $key => $entitiesIds) {
                    
                    if (count(array_intersect($entitiesIds, $idsFlush))){
                        $memcached->delete($key);

                        $warning[] = 'Cache key deleted : '.$key. ' cause updated/deleted or inserted entity\'s id was linked ('.implode(',',$entitiesIds).')';

                        // deleted entrie in memcached saved entities linked
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
