<?php
namespace Lioshi\WonderCacheBundle\Logger;
 
/**
 * Class to logg infos, errors and warnings
 * 
 */
class WonderCacheLogger 
{
    private $logs = array(
        'uri' => '',
        'infos' => array(),
        'warnings' => array(),
        'errors' => array()
        );
 
    public function addUri($uri)
    {
       $this->logs['uri'] = $uri;
    }

    public function addInfo($log, $entities = array())
    {
        $this->logs['infos'][] = array('log' => $log, 'entities' => $entities);
    }

    public function addWarning($log)
    {
        $this->logs['warnings'][] = $log;
    }
 
    public function addError($log)
    {
        $this->logs['errors'][] = $log;
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
