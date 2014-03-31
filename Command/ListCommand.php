<?php

namespace Lioshi\WonderCacheBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

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

      foreach ($memcached->getAllKeys() as $key => $displayKey) {
          $i++;
          $state = ($displayKey['empty'])?'<error> empty </error>':'';
          if ($displayKey['duration']!==false){ // false if no duration, 0 if infinite and 
            if ($displayKey['duration'] == 0){
              $durationInfos = ' <info>(infinite)</info>';
            } else { 
              $durationInfos = ' <info>('.$displayKey['duration'].' seconds)</info>';
            }
          } else {
            $durationInfos = '';
          }
          $output->writeln('<info>'.$i.'</info> <comment>'.$displayKey['name'].'</comment> '.$state.$durationInfos);
          $keys[$i] = $key;
      }

      if (!$i){
        $output->writeln('<info>No cache</info>');
      } else {
        // display cache content?
        print_r($this->getCacheContent($keys, $memcached, $output));
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
      '<info> Display which cache key content? (write identifier number) </info>',
      function($key)
        {
          return $key;
        }
    );

    if (!array_key_exists($key, $keys)) {
      throw new \Exception('Identifier number '.$key.' not exists');
    }

    return $memcached->get($keys[$key]);
  }

}
