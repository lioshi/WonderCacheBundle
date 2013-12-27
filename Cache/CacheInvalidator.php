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
        
// $fp = fopen("/data/www/testa/web/logInvalidatorCache.txt","w"); 

        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        // $uow->getScheduledCollectionDeletions() 
        // $uow->getScheduledCollectionUpdates()

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
        $memcached = $MemcacheTools->getMemCachedAllServers(); 

        $LinkedModelsToCachedKeys = $memcached->get($WonderCache->getLinkedEntitiesToCachedKeysFilename());
        // $LinkedModelsToCachedKeys = '__linkedModelsToCachedKeys';
        $cachelogs = count($LinkedModelsToCachedKeys)."\n";

        foreach ($classesToDelete as $classToDelete => $idsFlush) {

            $cachelogs .= 'Doctrine flush Classes to delete : '.$classToDelete."\n";
            $cachelogs .= 'Doctrine flush Ids               : '.implode(',',$idsFlush)."\n";

            if (isset($LinkedModelsToCachedKeys[$classToDelete])){
                foreach ($LinkedModelsToCachedKeys[$classToDelete] as $key => $entitiesIds) {
                    $cachelogs .= 'Ids entities of cache            : '.implode(',',$entitiesIds)."\n";
                    if (count(array_intersect($entitiesIds, $idsFlush))){
                        $memcached->delete($key);
                        $cachelogs .= 'Key deleted                      : '.$key."\n";
                        // deleted entrie in 
                        unset($LinkedModelsToCachedKeys[$classToDelete][$key]);
                        $memcached->set($WonderCache->getLinkedEntitiesToCachedKeysFilename(), $LinkedModelsToCachedKeys, 0);
                    }
                }
            }
        }

// fputs($fp, $cachelogs); 
        return;

    }

    
}
