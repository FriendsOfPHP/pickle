<?php
namespace Pickle\Console\Command;

use Pickle\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
    const RE_PACKAGE = '#^
        (?:pecl/)?
        (?<package>\w+)
        (?:
             \-(?<stability>beta|stable|alpha)
           | @(?<version>(?:\d+(?:\.\d+)*))
        )?
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
        $info = parse_url($path);

        $download = (
            (isset($info['scheme']) && in_array($info['scheme'], ['http', 'https', 'git'])) ||
            (isset($info['scheme']) === false  && is_dir($path) === false)
        );

        if ($download) {
            $package = $this->getHelper('package')->download($input, $output, $path, sys_get_temp_dir());

            if (null === $package) {
                throw new \InvalidArgumentException('Package not found: ' . $path);
            }

            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $package->getName();
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
