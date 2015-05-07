<?php

namespace Pickle\Downloader;

use Composer\Downloader\ArchiveDownloader;

class TGZDownloader extends ArchiveDownloader
{
    protected function extract($file, $path)
    {
        $archive = new \PharData($file);
        $archive->decompress()->extractTo($path, null, true);
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
