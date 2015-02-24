<?php

namespace Pickle\Base\Abstracts\Package\Convey;

use Composer\IO\ConsoleIO;
use Pickle\Package;
use Pickle\Package\JSON\Dumper;

abstract class Command
{
    protected $path;
    protected $version;
    protected $prettyVersion;
    protected $name;
    protected $stability;
    protected $url;
    protected $io;

    public function __construct($path, ConsoleIO $io)
    {
        $this->path = $path;
        $this->io = $io;

        $this->prepare();
    }

    abstract protected function prepare();


    public function execute($target, $no_convert)
    {
        $jsonLoader = new Package\JSON\Loader(new Package\Loader());
        $pickle_json = $target . DIRECTORY_SEPARATOR . 'composer.json';
        $package = null;

        if (file_exists($pickle_json)) {
            $package = $jsonLoader->load($pickle_json);
        }

        if (null === $package && $no_convert) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package) {
            if (file_exists($target . DIRECTORY_SEPARATOR . 'package2.xml')) {
                $pkg_xml = $target . DIRECTORY_SEPARATOR . 'package2.xml';
            } elseif (file_exists($target . DIRECTORY_SEPARATOR . 'package.xml')) {
                $pkg_xml = $target . DIRECTORY_SEPARATOR . 'package.xml';
            } else {
                throw new \Exception("package.xml not found");
            }

            $loader = new Package\XML\Loader(new Package\Loader());
            $package = $loader->load($pkg_xml);

            $dumper = new Dumper();
            $dumper->dumpToFile($package, $pickle_json);

            $package = $jsonLoader->load($pickle_json);
        }

        $package->setRootDir($target);

        return $package;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getPrettyVersion()
    {
        return $this->prettyVersion;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStability()
    {
        return $this->stability;
    }

    public function getUrl()
    {
        return $this->url;
    }
}
