<?php
namespace Pickle\Console\Command;

use Pickle\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $path = rtrim($input->getArgument('path'), '/\\');

        try {
            $package = new Package($path);
        } catch (\InvalidArgumentException $exception) {
            $package = new Package($path, new Package\XML\Parser($path));

            $formatter = $this->getHelper('formatter');
            $output->writeln($formatter->formatBlock(
                [
                    'This package use the old XML format.',
                    'Use the convert command to switch to pickle format'
                ],
                'fg=black;bg=yellow',
                true
            ));
        }

        $helper = $this->getHelper('package');
        $helper->showInfo($output, $package);
        $helper->showSummary($output, $package);
    }
}
