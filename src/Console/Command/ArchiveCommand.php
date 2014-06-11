<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

        $pkg = new Package($path);

        $arch = new Archive($pkg);
        $arch->create();
    }
}
