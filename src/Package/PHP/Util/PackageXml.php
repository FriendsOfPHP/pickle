<?php

namespace Pickle\Package\PHP\Util;

use Pickle\Package;
use Pickle\Package\Util\JSON\Dumper;

class PackageXml
{
    protected $xmlPath = null;
    protected $jsonPath = null;
    protected $package = null;

    public function __construct($path)
    {
        $names = array(
                        'package2.xml',
                        'package.xml',
                );

        foreach ($names as $fl) {
            $xml = $path.DIRECTORY_SEPARATOR.$fl;
            if (true === is_file($xml)) {
                $this->xmlPath = $xml;
                break;
            }
        }

        if (!$this->xmlPath) {
            throw new \InvalidArgumentException("The path '$path' doesn't contain package.xml");
        }

        $this->jsonPath = $path.DIRECTORY_SEPARATOR.'composer.json';
    }

    public function load()
    {
        $loader = new Package\PHP\Util\XML\Loader(new Package\Util\Loader());
        $this->package = $loader->load($this->xmlPath);

        if (!$this->package) {
            throw new \Exception("Failed to load '{$this->xmlPath}'");
        }

        return $this->package;
    }

    public function convertChangeLog()
    {
        if (!$this->package) {
            $this->load();
        }

        $convertCl = new ConvertChangeLog($this->xmlPath);
        $convertCl->parse();
        $convertCl->generateReleaseFile();
    }

    /* XXX maybe need a separate composer.json util */
    public function dump($fname = null)
    {
        if (!$this->package) {
            $this->load();
        }

        if ($fname) {
            $this->jsonPath = $fname;
        }
        $this->package->setVersion('');

        print_r($this->package);
        $dumper = new Dumper();
        $dumper->dumpToFile($this->package, $this->jsonPath);
    }

    public function getPackage()
    {
        if (!$this->package) {
            $this->load();
        }

        return $this->package;
    }

    public function getXmlPath()
    {
        return $this->xmlPath;
    }

    public function getJsonPath()
    {
        return $this->jsonPath;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
