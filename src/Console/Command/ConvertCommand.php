<?php
namespace Pickle\Console\Command;

use Pickle\Package\XML\Converter;
use Pickle\Package;
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
        $parser = new Package\XML\Parser($path);
        $convert = new Converter($path, $parser);

        $parser->parse();
        $package = $convert->convert();

        $output->writeln('<info>Successfully converted ' . $package->getName() . '</info>');

        $helper = $this->getHelper('package');
        $helper->showInfo($output, $package);
    }
}
