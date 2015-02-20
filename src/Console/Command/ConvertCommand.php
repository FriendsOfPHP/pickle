<?php
namespace Pickle\Console\Command;

use Pickle\Package\JSON\Dumper;
use Pickle\Package;
use Pickle\ConvertChangeLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $path = rtrim($input->getArgument('path'), '/\\');
        $xml = $path . DIRECTORY_SEPARATOR . 'package.xml';
        if (false === is_file($xml)) {
            throw new \InvalidArgumentException('File not found: ' . $xml);
        }

        $loader = new Package\XML\Loader(new Package\Loader());
        $package = $loader->load($xml);
        $package->setRootDir($path);
        $convertCl = new ConvertChangeLog($xml);
        $convertCl->parse();
        $convertCl->generateReleaseFile();
        $dumper = new Dumper();
        $dumper->dumpToFile($package, $path . DIRECTORY_SEPARATOR . 'composer.json');

        $output->writeln('<info>Successfully converted ' . $package->getPrettyName() . '</info>');

        $this->getHelper('package')->showInfo($output, $package);
    }
}
