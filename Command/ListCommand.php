<?php

namespace Lioshi\WonderCacheBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Provides a command-line interface to list memcache content
 */
class ListCommand extends ContainerAwareCommand
{

   /**
    * Configure the CLI task
    *
    * @return void
    */
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
                $output->writeln('<info>'.$i.'</info> <comment>'.$displayKey['name'].'</comment> '.$state);
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

  protected function getCacheContent($keys, $memcached, $output)
  {
    $key = $this->getHelper('dialog')->askAndValidate(
      $output,
      '<info> Display which cache key content? (put number) </info>',
      function($key)
        {
          return $key;
        }
    );

    if (!array_key_exists($key, $keys)) {
      throw new \Exception('number '.$key.' not exists');
    }

    return $memcached->get($keys[$key]);
  }

}
