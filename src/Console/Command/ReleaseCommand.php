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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('package');
        Util\TmpDir::set($input->getOption('tmp-dir'));
        /* TODO Rework this to use the Info package command */
        $cb = function (Interfaces\Package $package) use ($helper, $output) {
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
                $build->createTempDir($package->getUniqueNameForFs());
                $build->configure($force_opts);
                $build->make();
                $this->saveBuildLogs($input, $build);
            } catch (\Exception $e) {
                if ($input->getOption('pack-logs')) {
                    $release->packLog($build);
                } else {
                    $this->saveBuildLogs($input, $build);
                }

                $output->writeln('The following error(s) happened: '.$e->getMessage());
            }

            $args = array(
                'build' => $build,
            );

            try {
                $release->create($args);
                if ($input->getOption('pack-logs')) {
                    $release->packLog();
                }
            } catch (Exception $e) {
                if ($input->getOption('pack-logs')) {
                    $release->packLog();
                }
                $build->cleanup();
                throw new \Exception($e->getMessage());
            }
        } else {
            /* imply --source */
            try {
                $release->create();
                if ($input->getOption('pack-logs')) {
                    $release->packLog();
                }
            } catch (Exception $e) {
                if ($input->getOption('pack-logs')) {
                    $release->packLog();
                }
                throw new \Exception($e->getMessage());
            }
        }
        return 0;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
