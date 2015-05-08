<?php

namespace Pickle\Package\HHVM;

use Pickle\Base\Abstracts;

class Package extends Abstracts\Package implements \Pickle\Base\Interfaces\Package
{
    protected $path;

    public function setRootDir($path)
    {
        $this->path = $path;
    }

    public function getRootDir()
    {
        return $this->path;
    }

    public function getSourceDir()
    {
        $conf = glob("{$this->path}/config.cmake");
        if (!$conf) {
            throw new \Exception("Couldn't determine package source dir");
        }

        return dirname($conf[0]);
    }

    public function setStability($stability)
    {
        $this->stability = $stability;
    }

    public function getConfigureOptions()
    {
        return array();
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
