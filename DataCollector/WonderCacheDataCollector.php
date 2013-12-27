<?php
namespace Lioshi\WonderCacheBundle\DataCollector;

use Lioshi\WonderCacheBundle\Logger\WonderCacheLogger; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
 
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
        return 'WonderCache logs';
    }
 
    public function getNbrLogs()
    {
        return count($this->data['logs']);
    }
 
    public function getLogs()
    {
        return $this->data['logs'];
    }
}
