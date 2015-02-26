<?php

namespace Pickle\Package\PHP\Command;

use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;

use Pickle\Package;
use Pickle\Package\PHP\Util\ConvertChangeLog;
use Pickle\Package\Util\JSON\Dumper;

class Convert 
{
    protected $path;
    protected $cb;

    public function __construct($path, $cb = NULL)
    {
        $this->path = $path;
        $this->cb   = $cb;
    }

    public function process()
    {
        $path = rtrim($this->path, '/\\');
        $xml = $path . DIRECTORY_SEPARATOR . 'package.xml';
        if (false === is_file($xml)) {
            throw new \InvalidArgumentException('File not found: ' . $xml);
        }

        $loader = new Package\PHP\Util\XML\Loader(new Package\Util\Loader());
        $package = $loader->load($xml);
        $package->setRootDir($path);
        $convertCl = new ConvertChangeLog($xml);
        $convertCl->parse();
        $convertCl->generateReleaseFile();
        $dumper = new Dumper();
        $dumper->dumpToFile($package, $path . DIRECTORY_SEPARATOR . 'composer.json');

        if ($this->cb) {
            $cb = $this->cb;
            $cb($package);
        }
    }
}

