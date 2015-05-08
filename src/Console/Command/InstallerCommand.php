<?php

namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\Table;
use Pickle\Base\Interfaces\Package;
use Pickle\Base\Abstracts\Console\Command\BuildCommand;
use Pickle\Engine;
use Pickle\Package\Util\Windows;
use Pickle\Package\Command\Install;
use Pickle\Base\Util;

class InstallerCommand extends BuildCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('install')
            ->setDescription('Install a php extension')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Do not install extension'
            )->addOption(
                'php',
                null,
                InputArgument::OPTIONAL,
                'path to an alternative php (exec)'
            )->addOption(
                'ini',
                null,
                InputArgument::OPTIONAL,
                'path to an alternative php.ini'
            )->addOption(
                'source',
                null,
                InputOption::VALUE_NONE,
                'use source package'
            )->addOption(
                'save-logs',
                null,
                InputOption::VALUE_REQUIRED,
                'path to save the build logs'
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

    /**
     * @param string          $path
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function binaryInstallWindows($path, $input, $output)
    {
        $php = Engine::factory();
        $table = new Table($output);
        $table
            ->setRows([
               ['<info>'.$php->getName().' Path</info>', $php->getPath()],
               ['<info>'.$php->getName().' Version</info>', $php->getVersion()],
               ['<info>Compiler</info>', $php->getCompiler()],
               ['<info>Architecture</info>', $php->getArchitecture()],
               ['<info>Thread safety</info>', $php->getZts() ? 'yes' : 'no'],
               ['<info>Extension dir</info>', $php->getExtensionDir()],
               ['<info>php.ini</info>', $php->getIniPath()],
            ])
            ->render();

        $inst = Install::factory($path);
        $progress = $this->getHelperSet()->get('progress');
        $inst->setProgress($progress);
        $inst->setInput($input);
        $inst->setOutput($output);
        $inst->install();

        $deps_handler = new Windows\DependencyLib($php);
        $deps_handler->setProgress($progress);
        $deps_handler->setInput($input);
        $deps_handler->setOutput($output);

        $helper = $this->getHelperSet()->get('question');

        $cb = function ($choices) use ($helper, $input, $output) {
            $question = new ChoiceQuestion(
                'Multiple choices found, please select the appropriate dependency package',
                $choices
            );
            $question->setMultiselect(false);

            return $helper->ask($input, $output, $question);
        };

        foreach ($inst->getExtDllPaths() as $dll) {
            if (!$deps_handler->resolveForBin($dll, $cb)) {
                throw new \Exception('Failed to resolve dependencies');
            }
        }
    }

    /*  The most of this needs to be incapsulated into an extra Build class*/
    protected function sourceInstall($package, InputInterface $input, OutputInterface $output, $optionsValue = [], $force_opts = '')
    {
        $helper = $this->getHelperSet()->get('question');

        $build = \Pickle\Package\Command\Build::factory($package, $optionsValue);

        try {
            $build->prepare();
            $build->createTempDir($package->getUniqueNameForFs());
            $build->configure($force_opts);
            $build->make();
            $build->install();

            $this->saveBuildLogs($input, $build);
        } catch (\Exception $e) {
            $this->saveBuildLogs($input, $build);

            $output->writeln('The following error(s) happened: '.$e->getMessage());
            $prompt = new ConfirmationQuestion('Would you like to read the log?', true);
            if ($helper->ask($input, $output, $prompt)) {
                $output->write($build->getLog());
            }
        }
        $build->cleanup();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = rtrim($input->getArgument('path'), '/\\');
        Util\TmpDir::set($input->getOption('tmp-dir'));

        /* if windows, try bin install by default */
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $sourceRequested = $input->getOption('source');
            if (!$sourceRequested) {
                $this->binaryInstallWindows($path, $input, $output);

                return 0;
            }
        }

        $package = $this->getHelper('package')->convey($input, $output, $path);

    /* TODO Info package command should be used here. */
        $this->getHelper('package')->showInfo($output, $package);

        list($optionsValue, $force_opts) = $this->buildOptions($package, $input, $output);

        if ($input->getOption('dry-run')) {
            return 0;
        }

        $this->sourceInstall($package, $input, $output, $optionsValue, $force_opts);

        return 0;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
