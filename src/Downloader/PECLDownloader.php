<?php
namespace Pickle\Downloader;

use Pickle\Base\Interfaces\Package;

class PECLDownloader extends TGZDownloader
{
    protected function getFileName(Package $package, $path)
    {
        return parent::getFileName($package, $path) . '.tgz';
    }
}
