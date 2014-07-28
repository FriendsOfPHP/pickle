<?php
namespace Pickle\Console\Command;

use Pickle\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription('Display information about a PECL extension')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd)',
                getcwd()
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = rtrim($input->getArgument('path'), DIRECTORY_SEPARATOR);

        $package = $this->getHelper("package")->convey($input, $output, $path);

        $this->getHelper('package')->showInfo($output, $package);

        $output->writeln(['', trim($package->getDescription()), '']);

        $output->writeln('<info>Configure options</info>');
        $this->getHelper('package')->showOptions($output, $package);
    }
}
