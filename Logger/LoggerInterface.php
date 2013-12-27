<?php
namespace Lioshi\WonderCacheBundle\Logger;

/**
 * Interface to allow for DataCollector to retrieve logged calls
 */
interface LoggerInterface
{
    /**
     * Get the logged calls for this Memcached object
     *
     * @return array Array of calls made to the Memcached object
     */
    public function getLogs();

}
