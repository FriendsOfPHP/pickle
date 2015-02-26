<?php

namespace Pickle\Package\PHP\Command;

use Pickle\Base\Interfaces;
use Pickle\Package;

class Validate implements Interfaces\Package\Validate
{
    protected $path;
    protected $cb = NULL;

    public function __construct($path, $cb = NULL)
    {
    $this->path = $path;
    $this->cb = $cb;
    }

    public function process()
    {
        if (false === is_file($this->path . DIRECTORY_SEPARATOR . 'package.xml')) {
            throw new \InvalidArgumentException('File not found: ' . $this->path . DIRECTORY_SEPARATOR . 'package.xml');
        }

        $loader = new Package\PHP\Util\XML\Loader(new Package\Util\Loader());
        $package = $loader->load($this->path . DIRECTORY_SEPARATOR . 'package.xml');

        if ($this->cb) {
            $cb = $this->cb;
            $cb($package);
        }
    }
}

