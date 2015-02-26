<?php
namespace Pickle\Package\HHVM;

use Composer\Package\CompletePackage;

use Pickle\Base\Util\GitIgnore;

class Package extends CompletePackage implements \Pickle\Base\Interfaces\Package
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
