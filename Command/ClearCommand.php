<?php

namespace Lioshi\WonderCacheBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


/**
 * Provides a command-line interface for flushing memcached content
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
        ->setDescription('Delete all memcached items')
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
        $client = 'response';
        try {
            $memcached = $this->getContainer()->get('memcached.'.$client);

var_dump($memcached->getAllKeys());

            $output->writeln($memcached->flush()?'<info> Delete all cache OK </info>':'<error> ERROR </error>');
        } catch (\Exception $e) {
            $output->writeln($e."<error> Memcached client '$client' is not found</error>");
        }
   }

}
