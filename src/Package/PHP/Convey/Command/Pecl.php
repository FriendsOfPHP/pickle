<?php

namespace Pickle\Package\PHP\Convey\Command;

use Composer\Config;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;
use Pickle\Package\PHP;
use Pickle\Engine;
use Pickle\Downloader\PECLDownloader;
use Pickle\Package\Convey\Command\Type;

class Pecl extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    protected function prepare()
    {
        $engine = Engine::factory();

        if (Type::determinePecl($this->path, $matches) < 1) {
            throw new \Exception("Not valid PECL URI");
        }

        if ("php" != $engine->getName()) {
            throw new \Exception("PECL is only supported with PHP");
        }

        $this->name = $matches['package'];
        $this->url = 'https://pecl.php.net/get/' . $matches['package'];

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
        $package = \Pickle\Package::factory($this->name, $this->version, $this->prettyVersion);
        $package->setDistUrl($this->url);

        $package->setRootDir($target);

        $downloader = new PECLDownloader($this->io, new Config());
        if (null !== $downloader) {
            $downloader->download($package, $target);
        }

        unset($package, $downloader);
    }

    public function execute($target, $no_convert)
    {
        $this->fetch($target);

        $exe = new DefaultExecutor($this);
        return $exe->execute($target, $no_convert);
    }

    public function getType()
    {
        return Type::PECL;
    }
}
