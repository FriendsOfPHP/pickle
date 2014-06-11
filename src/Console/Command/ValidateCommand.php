<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Pickle\Validate;
use Pickle\PackageXmlParser;

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
                'Path to the PECL extension root directory (default pwd)'
            )
            ->addOption(
               'yell',
               null,
               InputOption::VALUE_NONE,
               'If set, the task will yell in uppercase letters'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');

        if (empty($path)) {
            $path = getcwd();
        }

        $packagexml_path = realpath($path . '/' . 'package.xml');

        $parser = new PackageXmlParser($packagexml_path);
        $package = $parser->parse();

        $validate = new Validate($package);

        if ($input->getOption('yell')) {
            $xml = strtoupper($xml);
        }

        $output->writeln($packagexml_path);
    }
}
