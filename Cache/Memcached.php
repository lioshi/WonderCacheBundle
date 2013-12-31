<?php
namespace Lioshi\WonderCacheBundle\Cache;

/**
 * Class used for extends Memcached
 * 
 */
class Memcached extends \Memcached  
{  
    private $prefix;

    public function __construct($servers, $options, $overrideoptions)   
    {  
        parent::__construct();  
            
        $this->prefix = "";

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
        foreach (parent::getAllKeys() as $key) {
            $allKeys[str_replace($this->prefix, '', $key)] = array(
                    'empty' => !$this->get(str_replace($this->prefix, '', $key)),
                    'name'  => $key
                );
        }

        return $allKeys;
    }
} 
