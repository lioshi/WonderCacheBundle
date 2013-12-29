<?php
namespace Lioshi\WonderCacheBundle\Logger;
 
class WonderCacheLogger 
{
    private $logs = array(
        'infos' => array(),
        'warnings' => array(),
        'invalidations' => array()
        );
 
    public function addInfo($log, $entities = array())
    {
        $this->logs['infos'][] = array('log' => $log, 'entities' => $entities);
    }

    public function addWarning($log)
    {
        $this->logs['warnings'][] = $log;
    }
 
    public function addInvalidation($log)
    {
        $this->logs['invalidations'][] = $log;
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
