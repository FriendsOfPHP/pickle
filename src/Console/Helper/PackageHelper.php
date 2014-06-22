<?php
namespace Pickle\Console\Helper;

use Composer\Package\PackageInterface;
use Pickle\Package;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class PackageHelper extends Helper
{
    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     * @api
     */
    public function getName()
    {
        return 'package';
    }

    public function showInfo(OutputInterface $output, PackageInterface $package)
    {
        $table = new Table($output);
        $table
            ->setRows([
                ['<info>Package name</info>', $package->getPrettyName()],
                ['<info>Package version (current release)</info>', $package->getPrettyVersion()],
                ['<info>Package status</info>', $package->getStability()]
            ])
            ->render();
    }
} 