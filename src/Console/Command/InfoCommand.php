<?php
namespace Pickle\Console\Command;

use Composer\Config;
use Composer\Downloader\TarDownloader;
use Composer\IO\ConsoleIO;
use Pickle\Downloader\PECLDownloader;
use Pickle\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
    const RE_PACKAGE = '#^
        (?:pecl/)?
        (?P<package>\w+)
        (?:
            \-(?P<stability>beta|stable|alpha)|
            @(?P<version>(?:\d+.?)+)|
            $
        )
    $#x';

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

        if (@is_dir($path) === false) {
            $package = $this->getHelper('package')->download($input, $output, $path, sys_get_temp_dir());

            if (null === $package) {
                throw new \InvalidArgumentException('Package not found: ' . $path);
            }

            $path = $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $package->getName();
        }

        $package = null;

        if (file_exists($path . DIRECTORY_SEPARATOR . 'pickle.json')) {
            $loader = new Package\JSON\Loader(new Package\Loader());
            $package = $loader->load($path . DIRECTORY_SEPARATOR . 'pickle.json');
        }

        if (null === $package && file_exists($path . DIRECTORY_SEPARATOR . 'package.xml')) {
            $loader = new Package\XML\Loader(new Package\Loader());
            $package = $loader->load($path . DIRECTORY_SEPARATOR . 'package.xml');
        }

        if (null === $package) {
            throw new \RuntimeException('No package definition found in ' . $path);
        }

        $this->getHelper('package')->showInfo($output, $package);
        $output->writeln(trim($package->getDescription()));
    }
}
