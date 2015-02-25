<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pickle\Base\Interfaces;
use Pickle\Package\Command\Release;

class ReleaseCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('release')
            ->setDescription('Package a PECL extension for release')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd)',
                getcwd()
            )
            ->addOption(
                'no-convert',
                null,
                InputOption::VALUE_NONE,
                'Disable package conversion'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$helper = $this->getHelper('package');
	
    	$cb = function(Interfaces\Package $package) use ($helper, $output) {
		/* TODO Rework this to use the Info package command */
		$helper->showInfo($output, $package);
	};
        $path = rtrim($input->getArgument('path'), '/\\');

	$release = Release::factory($path, $cb, $input->getOption('no-convert'));
	$release->create();
    }
}
