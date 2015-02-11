<?php
namespace Pickle\Console\Command;

use Pickle\Package\JSON\Dumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pickle\Archive;
use Pickle\Package;

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
        $path = rtrim($input->getArgument('path'), '/\\');
        $jsonLoader = new Package\JSON\Loader(new Package\Loader());
        $package = null;

        if (file_exists($path . DIRECTORY_SEPARATOR . 'pickle.json')) {
            $package = $jsonLoader->load($path . DIRECTORY_SEPARATOR . 'pickle.json');
        }

        if (null === $package && $input->getOption('no-convert')) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package && file_exists($path . DIRECTORY_SEPARATOR . 'package.xml')) {
            $loader = new Package\XML\Loader(new Package\Loader());
            $package = $loader->load($path . DIRECTORY_SEPARATOR . 'package.xml');

            $dumper = new Dumper();
            $dumper->dumpToFile($package, $path . DIRECTORY_SEPARATOR . 'pickle.json');

            $package = $jsonLoader->load($path . DIRECTORY_SEPARATOR . 'pickle.json');
        }

        if (null === $package) {
            throw new \RuntimeException("Cannot find $path".DIRECTORY_SEPARATOR."{pickle.json,package.xml}");
        }

        $package->setRootDir(realpath($path));

        $this->getHelper('package')->showInfo($output, $package);
        $arch = new Archive($package);
        $arch->create();
    }
}
