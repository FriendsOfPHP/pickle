<?php

namespace Pickle\Package\Convey\Command;

use Composer\Config;
use Pickle\Package\Convey\Command;

use Pickle\Package;
use Pickle\Downloader\TGZDownloader;

class Tgz extends AbstractCommand implements Command\Command
{
    protected function prepare()
    {
        $this->name = basename($this->path);
        $this->version = "unknown";
        $this->prettyVersion = "unknown";
        $this->url = $this->path;
    }

    protected function fetch($target, $no_convert)
    {
        $package = new Package($this->name, $this->version, $this->prettyVersion);

        $package->setDistUrl($this->url);
        $package->setRootDir($target);

        $downloader = new TGZDownloader($this->io, new Config());
        if (null !== $downloader) {
            $downloader->download($package, $target);
        }
    }

    public function execute($target, $no_convert)
    {
        $this->fetch($target, $no_convert);

        return parent::execute($target, $no_convert);
    }

    public function getType()
    {
        return Command\Type::TGZ;
    }
}
