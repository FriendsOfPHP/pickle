<?php

namespace Pickle\Package\PHP\Command;

use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;

use Pickle\Package;
use Pickle\Package\PHP\Util\PackageXml;

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

        $pkgXml = new PackageXml($path);
        $package = $pkgXml->getPackage();
        $package->setRootDir($path);

        $pkgXml->convertChangeLog();
        $pkgXml->dump();

        if ($this->cb) {
            $cb = $this->cb;
            $cb($package);
        }
    }
}

