<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Pickle\Package\Command\Convert;
use Pickle\Base\Util;

class ConvertCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('convert')
            ->setDescription('Convert package.xml to new format')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd)',
                getcwd()
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Util\TmpDir::set($input->getOption("tmp-dir"));
    	$helper = $this->getHelper('package');
    	$cb = function(\Pickle\Base\Interfaces\Package $package) use ($helper, $output) {
		$output->writeln('<info>Successfully converted ' . $package->getPrettyName() . '</info>');
		$helper->showInfo($output, $package);
	};
	$convert = Convert::factory($input->getArgument('path'), $cb);
	$convert->process();
    }
}

