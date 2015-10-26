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
        $stability = $package->getStability();
        $table
            ->setRows([
                ['<info>Package name</info>', $package->getPrettyName()],
                ['<info>Package version (current release)</info>', str_replace("-$stability", '', $package->getPrettyVersion())],
                ['<info>Package status</info>', $stability],
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
