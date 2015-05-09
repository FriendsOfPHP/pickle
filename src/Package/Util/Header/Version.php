<?php

namespace Pickle\Package\Util\Header;

use Composer\Package\Version\VersionParser;
use Pickle\Base\Interfaces;
use Pickle\Package\Util;

class Version
{
    protected $package;
    protected $header;
    protected $version;
    protected $macroName;

    public function __construct(Interfaces\Package $package)
    {
        $this->package = $package;
        $this->macroName = 'PHP_'.strtoupper($this->package->getSimpleName()).'_VERSION';
        $this->header = $this->findHeader();
        $this->version = $this->getVersionFromHeader();
    }

    protected function findHeader()
    {
        $header = $this->package->getSourceDir().DIRECTORY_SEPARATOR.'php_'.$this->package->getSimpleName().'.h';

        if (!file_exists($header) || !$this->fileHasVersionMacro($header)) {
            $headers = (array) glob($this->package->getSourceDir().DIRECTORY_SEPARATOR.'*.h');
            $found = false;
            foreach ($headers as $h) {
                if ($this->fileHasVersionMacro($h)) {
                    $header = $h;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new \Exception("No macro named {$this->macroName} was found in the headers. ".
                    'This macro is recommended to be defined with the current extension version');
            }
        }

        return $header;
    }

    public function fileHasVersionMacro($fname)
    {
        $cont = file_get_contents($fname);

        return false !== strstr($cont, $this->macroName);
    }

    public function getVersionFromHeader()
    {
        $cont = file_get_contents($this->header);
        $pat = ',define\s+'.$this->macroName.'\s+"(.*)",i';

        if (!preg_match($pat, $cont, $m)) {
            throw new \Exception("Couldn't parse the version defined in the {$this->macroName} macro ".
                "from the header '{$this->header}'");
        }

        return $m[1];
    }

    public function updateJSON()
    {
        if ($this->package->getPrettyVersion() == $this->version) {
            /* Don't touch, it's the same. */
            return;
        }

        $dumper = new Util\Dumper();
        $composer_json = $this->package->getRootDir().DIRECTORY_SEPARATOR.'composer.json';

        $this->package->replaceVersion((new VersionParser())->normalize($this->version), $this->version);

        $len = file_put_contents($composer_json, json_encode($dumper->dump($this->package), JSON_PRETTY_PRINT));

        if (!$len) {
            throw new \Exception("Failed to update '$package_json'");
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
