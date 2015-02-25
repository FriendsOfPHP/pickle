<?php
namespace Pickle\Package\HHVM;

use Composer\Package\CompletePackage;

use Pickle\Base\Util\GitIgnore;

class Package extends CompletePackage implements \Pickle\Base\Interfaces\Package
{
    public function setRootDir($path)
    {
    }

    public function getRootDir()
    {
    }
    
    public function getSourceDir()
    {
	    // TODO check by cmake file precence
    }
}
