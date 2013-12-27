<?php
namespace Lioshi\WonderCacheBundle\Logger;
 
class WonderCacheLogger implements LoggerInterface
{
    private $logs = array();
 
    public function add($infos)
    {
        $time = microtime(true);
        $this->logs['time'][] = $infos;
    }
 
    public function getLogs()
    {
        return $this->logs;
    }
}
