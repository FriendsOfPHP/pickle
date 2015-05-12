<?php

namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pickle\Base\Interfaces;
use Pickle\Package\Command\Info;
use Pickle\Base\Util;

class InfoCommand extends Command
{
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
        Util\TmpDir::set($input->getOption('tmp-dir'));
        $helper = $this->getHelper('package');

        $cb = function (Interfaces\Package\Info $info) use ($helper, $output) {
            /* TODO The part of the helper showing package info plus the
                concrete info class implementation should be
                reworked. The concrete Info class should provide
                information for this callback, whereby the output
                format and how it is shown should be controleld by
                the helper. */
            $helper->showInfo($output, $info->getPackage());
            $output->writeln(['', trim($info->getPackage()->getDescription()), '']);
            $output->writeln('<info>Configure options</info>');
            $helper->showOptions($output, $info->getPackage());
        };

        $path = rtrim($input->getArgument('path'), DIRECTORY_SEPARATOR);
        $package = $helper->convey($input, $output, $path);

        $info = Info::factory($package, $cb);
        $info->show();
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
