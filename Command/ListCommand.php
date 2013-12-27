<?php

namespace Lioshi\WonderCacheBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use Lioshi\WonderCacheBundle\Cache\MemcacheTools as MemcacheTools;

/**
 * Provides a command-line interface for flushing memcache content
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
        ->setDefinition(array(
            new InputArgument('client', InputArgument::REQUIRED, 'The client'),
            new InputArgument('prefix', InputArgument::OPTIONAL, 'List only cache keys with this prefix'),
        ))
        ;
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
        $client = $input->getArgument('client');
        try {
            $memcache = $this->getContainer()->get('memcache.'.$client);
            
            $MemcacheTools = new MemcacheTools($this->getContainer());
            $i=0;
            $keys = array();

            foreach ($MemcacheTools->getMemcacheKeys($client) as $key) {
              if ($input->getArgument('prefix') && $input->getArgument('prefix')){
                $prefix = $input->getArgument('prefix');
                if (substr($key, 0, strlen($prefix)) == $prefix){
                  $i++;
                  $state = ($memcache->get($key))?'':'<error> empty </error>';
                  $output->writeln('<info>'.$i.'</info> <comment>'.$key.'</comment> '.$state);
                  $keys[$i] = $key;
                }
              } else {
                $i++;
                $state = ($memcache->get($key))?'':'<error> empty </error>';
                $output->writeln('<info>'.$i.'</info> <comment>'.$key.'</comment> '.$state);
                $keys[$i] = $key;
              }
              
            }

            if (!$i){
              $output->writeln('<info>No cache</info>');
            } else {
              // display cache content?
              print_r($this->getCacheContent($keys, $memcache, $output));
            }

        } catch (ServiceNotFoundException $e) {
            $output->writeln("<error>client '$client' is not found</error>");
        }
   }

   /**
    * Choose the client
    *
    * @param InputInterface  $input  Input interface
    * @param OutputInterface $output Output interface
    *
    * @see Command
    * @return mixed
    */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('client')) {
            $client = $this->getHelper('dialog')->askAndValidate(
                $output,
                '<info> Please give the client (default): </info>',
                function($client)
                {
                   if (empty($client)) {
                      $client = 'default';
                      // throw new \Exception('client can not be empty');
                   }

                   return $client;
                }
            );
            $input->setArgument('client', $client);
        }

        if (!$input->getArgument('prefix')) {
            $prefix = $this->getHelper('dialog')->askAndValidate(
                $output,
                '<question>Please give the prefix and enter, or enter directly:</question>',
                function($prefix)
                {
                   return $prefix;
                }
            );
            $input->setArgument('prefix', $prefix);
        }
    }

    protected function getCacheContent($keys, $memcache, $output){
      
      $key = $this->getHelper('dialog')->askAndValidate(
          $output,
          '<question>Display which cache key content (write number) or enter:</question>',
          function($key)
          {
            return $key;
          }
      );

      if (!array_key_exists($key, $keys)) {
          throw new \Exception('number '.$key.' not exists');
      }

      return $memcache->get($keys[$key]);

    }

}
