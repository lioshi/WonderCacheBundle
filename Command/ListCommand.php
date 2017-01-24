<?php

namespace Lioshi\WonderCacheBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use Lioshi\WonderCacheBundle\Cache\WonderCache;

/**
 * Provides a command-line interface to list memcache contents
 * 
 */
class ListCommand extends ContainerAwareCommand
{

   protected function configure()
   {
      $this
        ->setName('wondercache:list')
        ->setDescription('List all Memcache items')
        ->setDefinition(array());
   }

   /**
    * Execute the CLI task
    *
    * @param InputInterface  $input  Command input
    * @param OutputInterface $output Command output
    *
    * @return void
    */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    try {
      $memcached = $this->getContainer()->get('memcached.response');

      $i=0;
      $keys = array();

      $WonderCache = new WonderCache($this->getContainer());
      $LinkedModelsToCachedKeys = $memcached->get($WonderCache->getLinkedEntitiesToCachedKeysFilename());

      foreach ($memcached->getAllKeys() as $key => $displayKey) {
          $i++;
          $state = ($displayKey['empty'])?'<error> empty </error>':'';
          if ($displayKey['duration']!==false){ // false if no duration, 0 if infinite and 
            
            $durationDatas = explode("|", $displayKey['duration']);
            $displayKeyDuration = $durationDatas[0];
            $displayKeyCreatedAt = date("Y-m-d H:i:s", $durationDatas[1]);

            if ($displayKeyDuration == 0){
              $durationInfos = ' <info>[lifetime infinite] created at '.$displayKeyCreatedAt.'</info>';
            } else { 
              $durationInfos = ' <info>[lifetime '.$displayKeyDuration.' seconds] created at '.$displayKeyCreatedAt.'</info>';
            }
          } else {
            $durationInfos = '';
          }
          $output->writeln('<comment>'.$displayKey['name'].'</comment> '.$state.$durationInfos);
          $keys[$i] = $key;

          // get entities linked
          foreach ($LinkedModelsToCachedKeys as $class => $cacheKeys) {
            foreach ($cacheKeys as $cacheKey => $entitiesIds) {
              if($cacheKey == $key){
                if(count($entitiesIds)){  
                  $listIds = implode(',', $entitiesIds);
                } else {
                  $listIds = 'ALL';
                }
                $output->writeln('  <info>'.$class.'</info> '.$listIds);
              }
            }
          }

      }
      $output->writeln('<fg=black;bg=green> '.$i.' cache\'s entries </>');


      // get stats
      $output->writeln(' ');
      $stats = $memcached->getStats();
      foreach ($stats as $server => $clusters) {
        foreach ($clusters as $cluster => $stats) {
            $usage = round($stats['bytes'] / $stats['limit_maxbytes'] * 100, 3);
            
            if($usage>=0) { $colorUsage = 'green';}
            if($usage>25){ $colorUsage = 'cyan';}
            if($usage>50){ $colorUsage = 'blue';}
            if($usage>75){ $colorUsage = 'magenta';}
            if($usage>95){ $colorUsage = 'red';}
            $output->writeln(' <fg=black;bg=yellow> '.$cluster.' </><fg=black;bg='.$colorUsage.'> usage '.$usage.'% </>');

            foreach ($stats as $stat => $value) {
                // $output->writeln('<comment> '.$stat.' </comment> <info> '.$value.' </info>');
                $output->writeln('<comment> '.$stat.' </comment> <info> '.$value.' </info>');
            }
        }
        $output->writeln(' ');
      }
            
      if (!$i){
        $output->writeln('<info>No cache</info>');
      } else {
        // display cache content?
        print_r($this->getCacheContent($keys, $memcached, $output));
        $output->writeln(' ');
      }


    } catch (ServiceNotFoundException $e) {
            $output->writeln("<error> Service memcached.response is not found</error>");
    }
  }

   /**
    * Get a displayable view of a content cache
    * @param  string $keys      
    * @param  Memcached $memcached 
    * @param  OutputInterface $output    
    * @return string    Content of cache's key
    */
  protected function getCacheContent($keys, $memcached, $output)
  {
    $key = $this->getHelper('dialog')->askAndValidate(
      $output,
      '<info> Key name cache content to display? </info>',
      function($key)
        {
          return $key;
        }
    );

    if($key){
        if (!in_array($key, $keys)) {
            $output->writeln("<error> Identifier number $key not exists </error>");
        } else {
            $output->writeln(' ');
            $output->writeln('<fg=white;bg=blue> '.$key.' content: </>');
            return $memcached->get($key);
        }
    }
  }
}
