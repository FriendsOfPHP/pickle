<?php
namespace Pickle\Base\Interfaces;

use Composer\Package\PackageInterface as ComposerPackageInterface;

interface Package extends ComposerPackageInterface {
    public function setRootDir($path);
    public function getRootDir();
    public function getSourceDir();
}

