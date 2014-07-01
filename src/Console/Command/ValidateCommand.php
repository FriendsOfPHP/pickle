<?php
namespace Pickle\Console\Command;

use Pickle\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('validate')
            ->setDescription('Validate a PECL extension')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd)',
                getcwd()
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = rtrim($input->getArgument('path'), '/\\');

        if (false === is_file($path . DIRECTORY_SEPARATOR . 'package.xml')) {
            throw new \InvalidArgumentException('File not found: ' . $path . DIRECTORY_SEPARATOR . 'package.xml');
        }

        $loader = new Package\XML\Loader(new Package\Loader());
        $package = $loader->load($path . DIRECTORY_SEPARATOR . 'package.xml');

        $this->getHelper('package')->showInfo($output, $package);
        $output->writeln(trim($package->getDescription()));
    }
}
