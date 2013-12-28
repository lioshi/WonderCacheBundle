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
        $memcached = $MemcacheTools->getMemCachedByClient('response'); // invalidation of memcached's client response

        $LinkedModelsToCachedKeys = $memcached->get($WonderCache->getLinkedEntitiesToCachedKeysFilename());
        // $LinkedModelsToCachedKeys = '__linkedModelsToCachedKeys';
        // $cachelogs = count($LinkedModelsToCachedKeys)."\n";

        foreach ($classesToDelete as $classToDelete => $idsFlush) {

            $this->container->get('wonder.cache.logger')->addInvalidation('Doctrine flush Classes to delete : '.$classToDelete);
            $this->container->get('wonder.cache.logger')->addInvalidation('Doctrine flush Ids               : '.implode(',',$idsFlush));

            if (isset($LinkedModelsToCachedKeys[$classToDelete])){
                foreach ($LinkedModelsToCachedKeys[$classToDelete] as $key => $entitiesIds) {
                    
                    $this->container->get('wonder.cache.logger')->addInvalidation('Ids entities of cache            : '.implode(',',$entitiesIds));
                    
                    if (count(array_intersect($entitiesIds, $idsFlush))){
                        $memcached->delete($key);

                        $this->container->get('wonder.cache.logger')->addInvalidation('Key deleted : '.$key);

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
