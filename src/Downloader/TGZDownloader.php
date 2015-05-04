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
