<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Pickle\PackageXmlParser;

class ValidateCommand extends Command
{
    /**
     *
     * Configuration
     *
     */
    protected function configure()
    {
        $this
            ->setName('validate')
            ->setDescription('Validate a PECL extension')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd)'
            );
    }

    /**
     *
     * Execute archive
     *
     * @param Inputinterface $input
     *
     * @param Outputinterface $output
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');

        if (empty($path)) {
            $path = getcwd();
        }

        $packagexml_path = realpath($path . '/' . 'package.xml');

        $parser = new PackageXmlParser($packagexml_path);
        $package = $parser->parse();

        $output->writeln($packagexml_path);
    }
}
