<?php

namespace Pickle\Package\PHP\Command;

use Pickle\Base\Interfaces;

class Info implements Interfaces\Package\Info
{
    protected $package;
    protected $cb;

    public function __construct(Interfaces\Package $package, $cb = null)
    {
        $this->package = $package;
        $this->cb   = $cb;
    }

    public function show()
    {
        if ($this->cb) {
            $cb = $this->cb;
            $cb($this);
        }
    }

    public function getPackage()
    {
        return $this->package;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
