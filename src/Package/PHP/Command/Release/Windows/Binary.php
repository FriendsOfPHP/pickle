<?php

namespace Pickle\Package\PHP\Command\Release\Windows;

use Pickle\Base\Interfaces;
use Pickle\Package;
use Pickle\Package\PHP\Util\PackageXml;
use Pickle\Package\Util\Header;

class Binary implements Interfaces\Package\Release
{
    /**
     * @var \Pickle\Base\Interfaces\Package
     */
    protected $pkg = null;

    /*
     * @var Closure
     */
    protected $cb = null;

    /*
     * @var bool
     */
    protected $noConvert = false;

    /**
     * Constructor
     *
     * @param string  $path
     * @param Closure $cb
     * @param bool    $noConvert
     */
    public function __construct($path, $cb = null, $noConvert = false)
    {
        $this->pkg       = $this->readPackage($path);
        $this->cb        = $cb;
        $this->noConvert = $noConvert;
    }

    protected function readPackage($path)
    {
        $jsonLoader = new Package\Util\JSON\Loader(new Package\Util\Loader());
        $package = null;

        if (file_exists($path.DIRECTORY_SEPARATOR.'composer.json')) {
            $package = $jsonLoader->load($path.DIRECTORY_SEPARATOR.'composer.json');
        }

        if (null === $package && $this->noConvert) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package) {
            try {
                $loader = new Package\PHP\Util\XML\Loader(new Package\Util\Loader());

                $pkgXml = new PackageXml($path);
                $pkgXml->dump();

                $jsonPath = $pkgXml->getJsonPath();

                $package = $jsonLoader->load($jsonPath);
            } catch (Exception $e) {
                /* pass for now, be compatible */
            }
        }

        if (null == $package) {
            /* Just ensure it's correct, */
            throw new \Exception("Couldn't read package info at '$path'");
        }

        $package->setRootDir(realpath($path));

        (new Header\Version($package))->updateJSON();

        return $package;
    }

    /**
     * Create package
     */
    public function create()
    {
    	throw new \Exception("coming soon ...");
    }
}

