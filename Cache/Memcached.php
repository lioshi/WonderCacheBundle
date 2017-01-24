<?php
namespace Lioshi\WonderCacheBundle\Cache;

use Lioshi\WonderCacheBundle\Cache;



/**
 * Class used for extends Memcached
 * 
 */
class Memcached extends \Memcached  
{  
    private $prefix;
    private $memServers;

    public function __construct($servers, $options, $overrideoptions)   
    {  
        parent::__construct();  
            
        $this->prefix = "";
        $this->memServers = $servers;

        // add servers         
        foreach ($servers as $server) {  
              
            if (!isset($server['dsn'])) {  
                throw new \LogicException("Memcached dsn must be defined for server");  
            }  
              
            if (!isset($server['port'])) {  
                throw new \LogicException("Memcached port must be defined for server");  
            }  
              
            if (!isset($server['weight'])) {  
                $server['weight'] = 0;  
            }  
        } 
        $this->addServers($servers); 

        

        // set options ovverride in config.yml
        if (isset($overrideoptions)) {
            foreach ($options as $key => $value) {
                if (isset($overrideoptions[$key])) {
                    if ($key == 'serializer') {
                        // serializer option needs to be supported and is a constant
                        if ($value != 'php' && !constant('Memcached::HAVE_' . strtoupper($value))) {
                            throw new \LogicException("Invalid serializer specified for Memcached: $value");
                        }
                        $newValue = constant('Memcached::SERIALIZER_' . strtoupper($value));
                    } elseif ($key == 'distribution') {
                        // distribution is defined as a constant
                        $newValue = constant('Memcached::DISTRIBUTION_' . strtoupper($value));
                    } else {
                        $newValue = $overrideoptions[$key];
                    }
                    if ($overrideoptions[$key]!=$value) {
                        // not default, update options
                        $constant = 'Memcached::OPT_'.strtoupper($key);
                        $this->setOption(constant($constant), $newValue);

                        if ($key == 'prefix_key') $this->prefix = $newValue;
                    }
                }
            }
        }
    }  

    /**
     * Get all keys on servers, display name with prefix but key doesn't need prefix to get/delete it, and then name store in key of return array
     * 
     * @return array formatted to store cache's key associated to their name (with prefix if configurated) and a boolean empty
     */
    public function getAllKeys(){
        $allKeys = array();

        // not worked...
        // $this->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        // var_dump($this->getOption(Memcached::OPT_BINARY_PROTOCOL));
        // var_dump(parent::getAllKeys());
        // $allKeysFromMemached = parent::getAllKeys();
        
        // get keys from all servers  
        $allKeysFromMemached =  array();
        foreach ($this->memServers as $server) {  
              
            if (!isset($server['dsn'])) {  
                throw new \LogicException("Memcached dsn must be defined for server");  
            }  
              
            if (!isset($server['port'])) {  
                throw new \LogicException("Memcached port must be defined for server");  
            }  
              
            if (!isset($server['weight'])) {  
                $server['weight'] = 0;  
            }  

            $allKeysFromMemached = array_merge($allKeysFromMemached, self::getMemcacheKeys($server['dsn'], $server['port']));
        } 

        foreach ($allKeysFromMemached as $key) {
            $allKeys[str_replace($this->prefix, '', $key)] = array(
                    'empty'     => !$this->get(str_replace($this->prefix, '', $key)),
                    'name'      => $key
                );
        }

        return $allKeys;
    }



    public function getStats(){
        $allKeys = array();
        
        // get keys from all servers  
        $allStats =  array();
        foreach ($this->memServers as $server) {  
              
            if (!isset($server['dsn'])) {  
                throw new \LogicException("Memcached dsn must be defined for server");  
            }  
              
            if (!isset($server['port'])) {  
                throw new \LogicException("Memcached port must be defined for server");  
            }  
              
            if (!isset($server['weight'])) {  
                $server['weight'] = 0;  
            }  

            $memcache_obj = new \Memcached;
            $memcache_obj->addServer($server['dsn'], $server['port']);
            $stats = $memcache_obj->getStats();
            
            $allStats[$server['dsn'].":".$server['port']] = $stats;
        } 

        return $allStats;
    }

    

    function getMemcacheKeys($host = '127.0.0.1', $port = 11211){
 
        $mem = @fsockopen($host, $port);
        if($mem === FALSE) return -1;
 
        // retrieve distinct slab
        $r = @fwrite($mem, 'stats items' . chr(10));
        if($r === FALSE) return -2;
 
        $slab = array();
        while( ($l = @fgets($mem, 1024)) !== FALSE){
                // sortie ?
                $l = trim($l);
                if($l=='END') break;
 
                $m = array();
                // <STAT items:22:evicted_nonzero 0>
                $r = preg_match('/^STAT\sitems\:(\d+)\:/', $l, $m);
                if($r!=1) return -3;
                $a_slab = $m[1];
 
                if(!array_key_exists($a_slab, $slab)) $slab[$a_slab] = array();
        }
 
        // recuperer les items
        reset($slab);
        foreach($slab AS $a_slab_key => &$a_slab){
                $r = @fwrite($mem, 'stats cachedump ' . $a_slab_key . ' 100' . chr(10));
                if($r === FALSE) return -4;
 
                while( ($l = @fgets($mem, 1024)) !== FALSE){
                        // sortie ?
                        $l = trim($l);
                        if($l=='END') break;
 
                        $m = array();
                        // ITEM 42 [118 b; 1354717302 s]
                        $r = preg_match('/^ITEM\s([^\s]+)\s/', $l, $m);
                        if($r!=1) return -5;
                        $a_key = $m[1];
 
                        $a_slab[] = $a_key;
                }
        }
 
        // close
        @fclose($mem);
        unset($mem);
 
        // transform it;
        $keys = array();
        reset($slab);
        foreach($slab AS &$a_slab){
                reset($a_slab);
                foreach($a_slab AS &$a_key) $keys[] = $a_key;
        }
        unset($slab);
 
        return $keys;
    }












} 
