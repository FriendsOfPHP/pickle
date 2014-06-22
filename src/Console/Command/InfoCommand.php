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

        if (is_dir($path) === false) {
            if (preg_match(self::RE_PACKAGE, $path, $matches) === 0) {
                throw new \InvalidArgumentException('Invalid package name: ' . $path);
            }

            $url = 'http://pecl.php.net/get/' . $matches['package'];

            if (isset($matches['stability']) && $matches['stability'] !== '') {
                $url .= '-' . $matches['stability'];
            } else {
                $matches['stability'] = 'stable';
            }

            if (isset($matches['version']) && $matches['version'] !== '') {
                $url .= '/' . $matches['version'];
                $prettyVersion = $matches['version'];
            } else {
                $matches['version'] = 'latest';
                $prettyVersion = 'latest-' . $matches['stability'];
            }

            $package = new Package($matches['package'], $matches['version'], $prettyVersion);
            $package->setDistUrl($url);

            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $matches['package'];
            $io = new ConsoleIO($input, $output, $this->getHelperSet());
            $downloader = new PECLDownloader($io, new Config());
            $downloader->download($package, $path);
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
