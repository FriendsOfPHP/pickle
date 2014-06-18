<?php
namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Pickle\Archive;
use Pickle\Package;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
                'Path to the PECL extension root directory (default pwd)',
                getcwd()
            )
            ->addOption(
                'no-convert',
                null,
                InputOption::VALUE_NONE,
                'Disable package conversion'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = rtrim($input->getArgument('path'), '/\\');

        try {
            $pkg = new Package($path);
        } catch (\InvalidArgumentException $exception) {
            if ($input->getOption('no-convert')) {
                throw new \RuntimeException('XML package are not supported. Please convert it before install');
            }

            $this->getApplication()
                ->find('convert')
                ->run($input, $output);

            $pkg = new Package($path);
        }

        $arch = new Archive($pkg);
        $arch->create();
    }
}
