<?php

namespace Pickle\Engine;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;

class HHVM extends Abstracts\Engine implements Interfaces\Engine
{

    public function __construct($phpCli = PHP_BINARY)
    {

    }

    public function hasSdk()
    {
        return false;
    }

    public function getName()
    {
        return "hhvm";
    }

    public function getCompiler()
    {
        return "";
    }

    public function getPath()
    {
        return PHP_BINARY;
    }

    public function getVersion()
    {
        return HHVM_VERSION;
    }

    protected function getParsedVersion($type)
    {
        if ($type < 1 || $type > 2) {
            throw new \Exception("Invalid version info requested");
        }

        if (!preg_match(",(\d*)\.(\d*)\.(\d*),", HHVM_VERSION, $m)) {
            throw new \Exception("Couldn't parse HHVM_VERSION");
        }

        return isset($m[$type + 1]) ? $m[$type + 1] : 0;
    }

    public function getMajorVersion()
    {
        return $this->getParsedVersion(0);
    }

    public function getMinorVersion()
    {
        return $this->getParsedVersion(1);
    }

    public function getReleaseVersion()
    {
        return $this->getParsedVersion(2);
    }

    public function getZts()
    {
        return true;
    }

    public function getExtensionDir()
    {
        return ini_get("extension_dir");
    }

    public function getIniPath()
    {
	    $ini = php_ini_loaded_file();

	    if (!$ini && file_exists("/etc/hhvm/php.ini")) {
		$ini = "/etc/hhvm/php.ini";
	    }

	    return $ini;
    }
}

