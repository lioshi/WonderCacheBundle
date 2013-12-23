<?php

namespace Lioshi\WonderCacheBundle\Cache;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ResponseCache
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->client = $container->getParameter('wondercache.response.client');
    }

    public function onKernelRequest(GetResponseEvent $event)
    {

// return; // do deactivate the listenner action

        $keyCacheName = 'response_'.md5($event->getRequest()->getUri());
                    
        if ($this->container->get('memcache.'.$this->client)->get($keyCacheName)){
                        
            $response = $this->container->get('memcache.'.$this->client)->get($keyCacheName);
            $response->headers->add(array('response_cache' => true ));
            $event->setResponse($response);
            return; 

        } else {

            return;

        }
       
    }


    public function onKernelResponse(FilterResponseEvent $event)
    {
        
// return $event->getResponse(); // do deactivate the listenner action


        $keyCacheName = 'response_'.md5($event->getRequest()->getUri());
            
        if ($this->container->get('memcache.'.$this->client)->get($keyCacheName)){
                
            return;

        } else {

            $response = $event->getResponse();

            // put this in page candidate to FPC
            // $response->headers->add(array('linked-entities' => '{sqdsqdsqdsqd}'));

            // save to memcached if response content has {entityModelLinks}
            $contentTypesAllowedInCache = array('application/json', 'text/html');
                
            if (array_key_exists('linked-entities', $response->headers)){
                // IMPORTANT : Enlever les valeurs de linked-entities avant de la renvoyer, pas de visiilité sur le client pour la var linked-entities
                $modelsEntities = true; 
            } else {
                $modelsEntities = false; 
            }
$modelsEntities = true; 
            if (
                in_array($response->headers->get('content-type'), $contentTypesAllowedInCache) &&
                $modelsEntities
                ){

                $this->container->get('memcache.'.$this->client)->set($keyCacheName, $response, 0, array(
                        
                            // how get the models and entities id link to this page?
                            // how get via response?

                        ));
                }

                return $response;

            }
        

    }


}
