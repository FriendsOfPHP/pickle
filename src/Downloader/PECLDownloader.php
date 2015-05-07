<?php

namespace Pickle\Downloader;

use Composer\Package\PackageInterface as Package;

class PECLDownloader extends TGZDownloader
{
    protected function getFileName(Package $package, $path)
    {
        return parent::getFileName($package, $path).'.tgz';
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
