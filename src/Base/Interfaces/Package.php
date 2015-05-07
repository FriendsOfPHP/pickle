<?php

namespace Pickle\Base\Interfaces;

use Composer\Package\PackageInterface as ComposerPackageInterface;

interface Package extends ComposerPackageInterface
{
    public function setRootDir($path);
    public function getRootDir();
    public function getSourceDir();
    public function setStability($stability);
    public function getConfigureOptions();
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
