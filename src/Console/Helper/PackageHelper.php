<?php
namespace Pickle\Console\Helper;

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
     *
     * @api
     */
    public function getName()
    {
        return 'package';
    }

    public function showInfo(OutputInterface $output, Package $package)
    {
        $releases = array_map(
            function ($release) {
                return $release['version'];
            },
            $package->getPastReleases()
        );

        $list = $sep = '';
        foreach ($releases as $k => $release) {
            $list .= $sep . $release;

            if ($k > 0 && $k % 5 === 0) {
                $sep = PHP_EOL;
            } else {
                $sep = ', ';
            }
        }

        $table = new Table($output);
        $table
            ->setRows([
                ['<info>Package name</info>', $package->getName()],
                ['<info>Package version (current release)</info>', $package->getVersion()],
                ['<info>Package status</info>', $package->getStatus()],
                [
                    '<info>Previous release(s)</info>',
                    $list
                ]
            ])
            ->render();
    }

    public function showSummary(OutputInterface $output, Package $package)
    {
        $output->write([
            PHP_EOL,
            $package->getSummary(),
            PHP_EOL,
            trim($package->getDescription()) . PHP_EOL
        ]);
    }
} 