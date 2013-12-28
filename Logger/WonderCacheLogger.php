<?php
namespace Lioshi\WonderCacheBundle\Logger;
 
class WonderCacheLogger 
{
    private $logs = array(
        'infos' => array(),
        'warnings' => array(),
        'invalidations' => array()
        );
 
    public function addInfo($log)
    {
        $time = microtime(true);
        $this->logs['infos'][] = $log;
    }

    public function addWarning($log)
    {
        $time = microtime(true);
        $this->logs['warnings'][] = $log;
    }
 
    public function addInvalidation($log)
    {
        $time = microtime(true);
        $this->logs['invalidations'][] = $log;
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
