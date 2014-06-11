<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Pickle\Validate;
use Pickle\ConvertXml;
use Pickle\PackageXmlParser;

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

		$path = realpath($path);
		$parser = new PackageXmlParser($path);
		$package = $parser->parse();

		$convert = new ConvertXml($package, dirname($path));
		$convert->maintainers();
		$convert->summary();
		$convert->release();
		$convert->changelog();
		$convert->extsrcrelease();

		if (!file_exists("LICENSE")) {
			$convert->license();
		}
		$convert->generateJson();
        $output->writeln("done.");
    }
}