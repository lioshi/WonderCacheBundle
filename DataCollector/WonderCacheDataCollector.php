<?php
namespace Lioshi\WonderCacheBundle\DataCollector;

use Lioshi\WonderCacheBundle\Logger\WonderCacheLogger; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
 
/**
 * Class to get data from collector
 * 
 */
class WonderCacheDataCollector extends DataCollector 
{
    private $logger;
 
    public function __construct(WonderCacheLogger $logger)
    {
        $this->logger = $logger;
    }
 
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if ($this->logger) {
            $this->data = array('logs' => $this->logger->getLogs());
        } else {
            $this->data = array('logs' => array());
        }
    }
 
    public function getName()
    {
        return 'wondercache';
    }

    public function getUri()
    {
        return $this->data['logs']['uri'];
    }

    public function getNbrLogs()
    {
        return count($this->data['logs']);
    }
 
    public function getLogs()
    {
        return $this->data['logs'];
    }

    public function getNbrLogsInfos()
    {
        return count($this->data['logs']['infos']);
    }

    public function getLogsInfos()
    {
        return $this->data['logs']['infos'];
    }

    public function getNbrLogsWarnings()
    {
        return count($this->data['logs']['warnings']);
    }

    public function getLogsWarnings()
    {
        return $this->data['logs']['warnings'];
    }

    public function getNbrLogsErrors()
    {
        return count($this->data['logs']['errors']);
    }

    public function getLogsErrors()
    {
        return $this->data['logs']['errors'];
    }

    public function getIconBase64($type)
    {
        return base64_encode(file_get_contents(dirname(__FILE__).'/../Resources/images/icon_'.$type.'.png'));
    }
    
}
