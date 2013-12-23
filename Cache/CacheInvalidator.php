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
                if (is_array($classesToDelete[get_class($entity)])){
                    $a = $classesToDelete[get_class($entity)];
                } else {
                    $a = array();
                }
                $classesToDelete[get_class($entity)] = array_merge($idsFlush, $a);
            }
        }

        $loggingMemcache = new LoggingMemcache;
        $memcached = $this->getMemCached();

        $LinkedModelsToCachedKeys = $memcached->get($loggingMemcache->getLinkedModelsToCachedKeysName());
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
                    }
                }
            }
        }

// fputs($fp, $cachelogs); 
        return;

    }

    /**
     * get all keys from memecahced servers hosts in parameters
     * @return [type] [description]
     */
    public function getMemcacheKeys() {

        return $this->getMemCached()->getAllKeys();
    } 

    private function getMemCached() {

        $paramMemcachehosts = $this->container->getParameter('wondercache.memcached.clients');  // get parameters hosts for memcached 
        // $servers = array(
        //     array('mem1.domain.com', 11211),
        //     array('mem2.domain.com', 11211)
        // );
        foreach ($paramMemcachehosts as $paramMemcachehost) {
            foreach ($paramMemcachehost['hosts'] as $host) {
                $servers[] = array($host['dsn'],$host['port']);
            }
        }

        $memcache = new \Memcached;
        $memcache->addServers($servers); // connect to those servers

        return $memcache;
    }

 
    
}
