<?php

/**
 * Pickle
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2015-2015, Pickle community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
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
            )
            ->addOption(
                'php',
                null,
                InputOption::VALUE_REQUIRED,
                'path to an alternative php (exec)'
            )
            ->addOption(
                'ini',
                null,
                InputOption::VALUE_REQUIRED,
                'path to an alternative php.ini'
            )
            ->addOption(
                'source',
                null,
                InputOption::VALUE_NONE,
                'use source package'
            )
            ->addOption(
                'save-logs',
                null,
                InputOption::VALUE_REQUIRED,
                'path to save the build logs'
            )
            ->addOption(
                'tmp-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'path to a custom temp dir',
                sys_get_temp_dir()
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
    protected function binaryInstallWindows($path, InputInterface $input, OutputInterface $output)
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

        $progress = new ProgressBar($output, 50);
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
