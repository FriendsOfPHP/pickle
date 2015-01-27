<?php
namespace Pickle;

use Composer\Package\PackageInterface as ComposerPackageInterface;

interface PackageInterface extends ComposerPackageInterface {
    public function setRootDir($path);
}

