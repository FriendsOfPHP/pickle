<?php
namespace Pickle\Downloader;

use Composer\Package\PackageInterface;


class PECLDownloader extends TGZDownloader
{
    protected function getFileName(PackageInterface $package, $path)
    {
        return parent::getFileName($package, $path) . '.tgz';
    }
}
