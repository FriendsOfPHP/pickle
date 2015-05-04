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
