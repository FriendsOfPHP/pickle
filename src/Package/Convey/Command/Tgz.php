<?php

namespace Pickle\Package\Convey\Command;

use Composer\Config;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;
use Pickle\Package;
use Pickle\Downloader\TGZDownloader;
use Pickle\Package\Convey\Command\Type;

class Tgz extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    protected function prepare()
    {
        $this->name = basename($this->path);
        $this->version = "unknown";
        $this->prettyVersion = "unknown";
        $this->url = $this->path;
    }

    protected function fetch($target)
    {
        $package = Package::factory($this->name, $this->version, $this->prettyVersion);

        $package->setDistUrl($this->url);
        $package->setRootDir($target);

        $downloader = new TGZDownloader($this->io, new Config());
        if (null !== $downloader) {
            $downloader->download($package, $target);
        }
    }

    public function execute($target, $no_convert)
    {
        $this->fetch($target);

    $exe = DefaultExecutor::factory($this);
        return $exe->execute($target, $no_convert);
    }

    public function getType()
    {
        return Type::TGZ;
    }
}
