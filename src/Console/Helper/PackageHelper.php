<?php

namespace Pickle\Console\Helper;

use Composer\IO\ConsoleIO;
use Pickle\Base\Interfaces;
use Pickle\Package\Convey;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PackageHelper extends Helper
{
    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'package';
    }

    public function showInfo(OutputInterface $output, Interfaces\Package $package)
    {
        $table = new Table($output);
        $table
            ->setRows([
                ['<info>Package name</info>', $package->getPrettyName()],
                ['<info>Package version (current release)</info>', $package->getPrettyVersion()],
                ['<info>Package status</info>', $package->getStability()],
            ])
            ->render();
    }

    public function showOptions(OutputInterface $output, Interfaces\Package $package)
    {
        $table = new Table($output);
        $table->setHeaders(['Type', 'Description', 'Default']);

        foreach ($package->getConfigureOptions() as $option) {
            $default = $option->default;

            if ($option->type === 'enable') {
                $option->type = '<fg=yellow>'.$option->type.'</fg=yellow>';
                $default = $default ? '<fg=green>yes</fg=green>' : '<fg=red>no</fg=red>';
            }

            $table->addRow([
                $option->type,
                wordwrap($option->prompt, 40, PHP_EOL),
                $default,
            ]);
        }

        $table->render();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param $url
     * @param $path
     *
     * @return Pickle\Base\Interfaces\Package
     */
    public function convey(InputInterface $input, OutputInterface $output, $path, $target = null)
    {
        $helperSet = $this->getHelperSet();
        $io = new ConsoleIO($input, $output, ($helperSet ? $helperSet : new HelperSet()));

        $no_convert = $input->hasOption('no-convert') ? $input->getOption('no-convert') : false;

        return (new Convey($path, $io))->deliver($target, $no_convert);
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
