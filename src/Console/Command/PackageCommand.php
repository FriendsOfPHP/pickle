<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Pickle\Archive;
use Pickle\Package;

class ArchiveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('archive')
            ->setDescription('Package a PECL extension')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
		$path = realpath($path);
		$json = json_decode(file_get_contents($path));

		$parser = new PackageParser($path . "/package.xml");
		$package = $parser->parse();
		$rel = glob($path . "/RELEASE-*");
		$last_release = $rel[count($rel) - 1];
		$last_release = str_replace('RELEASE-', '', basename($last_release));

		$archive_name = strtolower($package->name . "-$last_release");
		var_dump($archive_name);
		$package = new Archive(getcwd() . '/' . $archive_name, $path);
		$package->create();
    }
}