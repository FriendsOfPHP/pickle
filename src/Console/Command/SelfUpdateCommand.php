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

namespace Pickle\Console\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    protected const PHAR_NAME = 'pickle.phar';

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates pickle.phar to the latest version.')
            ->addOption(
                'unstable',
                null,
                InputOption::VALUE_NONE,
                'Update to a non-stable version'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = new Updater(null, false, Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setPackageName('friendsofphp/pickle');
        $updater->getStrategy()->setPharName(self::PHAR_NAME);
        $updater->getStrategy()->setCurrentLocalVersion($this->getApplication()->getVersion());

        if ($input->getOption('unstable')) {
            $updater->getStrategy()->setStability('unstable');
        }

        if ($updater->update() === false) {
            $output->writeln('<info>Already up-to-date.</info>');
        } else {
            $output->writeln('<info>' . $updater->getLocalPharFileBasename() . ' has been updated!</info>');
        }
        return 0;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
