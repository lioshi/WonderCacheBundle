<?php

namespace Lioshi\WonderCacheBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use Lioshi\WonderCacheBundle\Cache\MemcacheTools as MemcacheTools;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Provides a command-line interface for flushing memcache content
 */
class ClearCommand extends ContainerAwareCommand
{

   /**
    * Configure the CLI task
    *
    * @return void
    */
   protected function configure()
   {
      $this
        ->setName('wondercache:clear')
        ->setDescription('Invalidate all Memcache items')
        ->setDefinition(array(
            // new InputArgument('client', InputArgument::REQUIRED, 'The client'),
            // new InputArgument('prefix', InputArgument::OPTIONAL, 'Delete only cache keys with this prefix'),
        ));
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
        // $client = $input->getArgument('client');
        $client = 'response';
        try {
            $memcached = $this->getContainer()->get('memcached.'.$client);
            
            // total flush or delete by prefix?
            // if ($input->getArgument('prefix') && $input->getArgument('prefix') != ''){
            //   $prefix = $input->getArgument('prefix');
            //   $MemcacheTools = new MemcacheTools($this->getContainer());
            //   $i=0;
            //   foreach ($MemcacheTools->getMemcacheKeys($client) as $key) {
            //     // $output->writeln('<comment>'.$key.'</comment>');
            //     if (substr($key, 0, strlen($prefix)) == $prefix){
            //       $i++;
            //       $output->writeln($memcached->delete($key)?'<info>Delete cache key "'.$key.'" OK</info>':'<error>Delete cache key "'.$key.'" ERROR</error>');
            //     }
            //   }
            //   if ($i){
            //     $output->writeln($messageCacheClear);
            //   } else {
            //     $output->writeln('<info>No cache delete</info>');
            //   }
            // } else {
              $output->writeln($memcached->flush()?'<info>Delete all cache OK</info>':'<error>Delete all cache ERROR</error>');
            // }
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
        // if (!$input->getArgument('client')) {
        //     $client = $this->getHelper('dialog')->askAndValidate(
        //         $output,
        //         '<question>Please give the client (response):</question>',
        //         function($client)
        //         {
        //            if (empty($client)) {
        //               $client = 'response';
        //               // throw new \Exception('client can not be empty');
        //            }

        //            return $client;
        //         }
        //     );
        //     $input->setArgument('client', $client);
        // }

        // if (!$input->getArgument('prefix')) {
        //     $prefix = $this->getHelper('dialog')->askAndValidate(
        //         $output,
        //         '<question>Please give the prefix and enter, or enter directly:</question>',
        //         function($prefix)
        //         {
        //            return $prefix;
        //         }
        //     );
        //     $input->setArgument('prefix', $prefix);
        // }
    }

}
