<?php

/*
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

namespace Pickle\Base\Abstracts\Console\Command;

use Exception;
use Pickle\Base\Interfaces\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

abstract class BuildCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the PECL extension root directory (default pwd), archive or extension name',
                getcwd()
            )
            ->addOption(
                'no-convert',
                null,
                InputOption::VALUE_NONE,
                'Disable package conversion'
            )
            ->addOption(
                'defaults',
                null,
                InputOption::VALUE_NONE,
                'Use defaults configure options values'
            )->addOption(
                'source',
                null,
                InputOption::VALUE_NONE,
                'use source package'
            )->addOption(
                'with-configure-options',
                null,
                InputOption::VALUE_REQUIRED,
                'path to the additional configure options'
            )->addOption(
                'save-logs',
                null,
                InputOption::VALUE_REQUIRED,
                'path to save the build logs'
            );
    }

    protected function saveBuildLogs(InputInterface $input, $build)
    {
        $save_log_path = $input->getOption('save-logs');
        if ($save_log_path) {
            $build->saveLog($save_log_path);
        }
    }

    protected function buildOptions(Package $package, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelperSet()->get('question');

        $force_opts = $input->getOption('with-configure-options');

        if ($force_opts) {
            if (!file_exists($force_opts) || is_dir($force_opts) || !is_readable($force_opts)) {
                throw new Exception("File '{$force_opts}' is unusable");
            }

            if (DIRECTORY_SEPARATOR !== '\\' && preg_match('_^/dev/fd/\d+$_', $force_opts)) {
                // https://bugs.php.net/bug.php?id=53465
                $force_opts = str_replace('/dev/', 'php://', $force_opts);
            }
            $force_opts = preg_replace(',\\s+,', ' ', file_get_contents($force_opts));

            return [null, $force_opts];
        }

        $options = $package->getConfigureOptions();
        $optionsValue = [];

        foreach ($options as $name => $opt) {
            /* enable/with-<extname> */
            if ($name == $package->getName() || str_replace('-', '_', $name) == $package->getName()) {
                $optionsValue[$name] = (object) [
                    'type' => $opt->type,
                    'input' => true,
                ];

                continue;
            }

            if ($input->getOption('defaults')) {
                $value = $opt->default;
            } else {
                if ($opt->type == 'enable') {
                    $prompt = new ConfirmationQuestion($opt->prompt . ' (default: ' . ($opt->default ? 'yes' : 'no') . '): ', $opt->default);
                } else {
                    $prompt = new Question($opt->prompt . ' (default: ' . ($opt->default ?: '') . '): ', $opt->default);
                }

                $value = $helper->ask($input, $output, $prompt);
            }

            $optionsValue[$name] = (object) [
                'type' => $opt->type,
                'input' => $value,
            ];
        }

        return [$optionsValue, null];
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
