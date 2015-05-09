<?php

namespace Pickle\Package\PHP\Command;

use Pickle\Base\Interfaces;
use Pickle\Package\PHP\Util\PackageXml;

class Validate implements Interfaces\Package\Validate
{
    protected $path;
    protected $cb = null;

    public function __construct($path, $cb = null)
    {
        $this->path = $path;
        $this->cb = $cb;
    }

    public function process()
    {
        $pkgXml = new PackageXml($this->path);
        $package = $pkgXml->getPackage();

        if ($this->cb) {
            $cb = $this->cb;
            $cb($package);
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
