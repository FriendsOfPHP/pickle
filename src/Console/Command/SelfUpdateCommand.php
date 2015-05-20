<?php

namespace Pickle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\Exception;

class SelfUpdateCommand extends Command
{
    const PHAR_NAME = 'pickle.phar';

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
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
