<?php

namespace Pickle\Package\Convey\Command;

use Composer\Config;

use Pickle\Package;
use Pickle\Downloader\PECLDownloader;
use Pickle\Package\Convey\Command\Command;
use Pickle\Package\Convey\Command\Type;

class Pecl extends AbstractCommand implements Command
{
    protected function prepare()
    {
        if (Type::determinePecl($this->path, $matches) < 1) {
            throw new \Exception("Not valid pecl URI");
        }

        $this->name = $matches['package'];
        $this->url = 'http://pecl.php.net/get/' . $matches['package'];

        if (isset($matches['stability']) && '' !== $matches['stability']) {
            $this->stability = $matches['stability'];
            $this->url .= '-' . $matches['stability'];
        } else {
            $this->stability = 'stable';
        }

        if (isset($matches['version']) && '' !== $matches['version']) {
            $this->url .= '/' . $matches['version'];
            $this->prettyVersion = $matches['version'];
            $this->version = $matches['version'];
        } else {
            $this->version = 'latest';
            $this->prettyVersion = 'latest-' . $this->stability;
        }

    }

    protected function fetch($target)
    {
        $package = new Package($this->name, $this->version, $this->prettyVersion);
        $package->setDistUrl($this->url);

        $package->setRootDir($target);

        $downloader = new PECLDownloader($this->io, new Config());
        if (null !== $downloader) {
            $downloader->download($package, $target);
        }
    }

    public function execute($target, $no_convert)
    {
        $this->fetch($target);

        return parent::execute($target, $no_convert);
    }

    public function getType()
    {
        return Type::PECL;
    }
}
