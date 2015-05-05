<?php

namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts\Console\Command\BuildCommand;
use Pickle\Package\Command\Release;
use Pickle\Base\Util;

class ReleaseCommand extends BuildCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('release')
            ->setDescription('Package a PECL extension for release')
        /* TODO: make it to take value like zip, tgz, etc. should this functionality be expanded */
            ->addOption(
                'binary',
                null,
                InputOption::VALUE_NONE,
                'create binary package'
            )
            ->addOption(
                'pack-logs',
                null,
                InputOption::VALUE_NONE,
                'package build logs'
            );

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->addOption(
                'binary',
                null,
                InputOption::VALUE_NONE,
                'use binary package'
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('package');
        Util\TmpDir::set($input->getOption('tmp-dir'));

        $cb = function (Interfaces\Package $package) use ($helper, $output) {
        /* TODO Rework this to use the Info package command */
        $helper->showInfo($output, $package);
    };
        $path = rtrim($input->getArgument('path'), '/\\');

        /* Getting package unpacked first, then use the path*/
        $package = $this->getHelper('package')->convey($input, $output, $path);
        $release = Release::factory($package->getRootDir(), $cb, $input->getOption('no-convert'), $input->getOption('binary'));

        if ($input->getOption('binary')) {
            list($optionsValue, $force_opts) = $this->buildOptions($package, $input, $output);

            $build = \Pickle\Package\Command\Build::factory($package, $optionsValue);

            try {
                $build->prepare();
                $build->createTempDir($package->getName().$package->getVersion());
                $build->configure($force_opts);
                $build->make();
                $this->saveBuildLogs($input, $build);
            } catch (\Exception $e) {
                $this->saveBuildLogs($input, $build);

                $output->writeln('The following error(s) happened: '.$e->getMessage());
            }

            $args = array(
                'build' => $build,
                'pack_logs' => $input->getOption("pack-logs"),
            );
            $release->create($args);

            $build->cleanup();
        } else {
            /* imply --source */
        $release->create();
        }
    }
}
