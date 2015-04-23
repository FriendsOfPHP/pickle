<?php

namespace Pickle\Package\PHP\Command;

use Pickle\Base\Interfaces;
use Pickle\Package;

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
        $pkgXml = new PackageXml($path);
        $package = $pkgXml->getPackage();

        if ($this->cb) {
            $cb = $this->cb;
            $cb($package);
        }
    }
}
