<?php
namespace Lioshi\WonderCacheBundle\Cache;

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

        // set options
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

    // public function get($key){
    //     return str_replace($this->prefix, '', $key);
    //     return parent::get(str_replace($this->prefix, '', $key));
    // }
} 
