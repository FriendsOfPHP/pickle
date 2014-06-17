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
        $package = new Package\XML\Parser($path);
        $package->parse();

        $table = $this->getHelper('table');
        $table
            ->setRows([
               ['<info>Packager version</info>', $package->getPackagerVersion()],
               ['<info>XML version</info>', $package->getXMLVersion()],
               ['<info>Package name</info>', $package->getName()],
               ['<info>Package version</info>', $package->getVersion()],
               ['<info>Extension</info>', $package->getProvidedExtension()],
            ])
            ->render($output);
    }
}
