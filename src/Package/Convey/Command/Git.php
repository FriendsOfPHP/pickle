<?php

namespace Pickle\Package\Convey\Command;

use Composer\Config;
use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;
use Pickle\Package;
use Composer\Downloader\GitDownloader;

class Git extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    protected function prepare()
    {
        if (Type::determineGit($this->path, $matches) < 1) {
            throw new \Exception('Not valid git URI');
        }

        $this->name = $matches['package'];
        $this->version = isset($matches['reference']) ? $matches['reference'] : 'master';
        $this->prettyVersion = $this->version;
        $this->url = preg_replace('/#.*$/', '', $this->path);
    }

    protected function fetch($target)
    {
        $package = Package::factory($this->name, $this->version, $this->prettyVersion);

        $package->setSourceType('git');
        $package->setSourceUrl($this->url);
        $package->setSourceReference($this->version);
        $package->setRootDir($target);

        $downloader = new GitDownloader($this->io, new Config());
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
        return Type::GIT;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
