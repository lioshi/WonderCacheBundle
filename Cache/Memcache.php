<?php
namespace Lioshi\WonderCacheBundle\Cache;

class Memcache extends \Memcached 
{
    public function getAllKeysx(){
        // return array('etertertret','ertret');
        return parent::getAllKeys();
    }
}
