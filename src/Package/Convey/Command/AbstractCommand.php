<?php

namespace Pickle\Package\Convey\Command;

use Composer\IO\ConsoleIO;

use Pickle\Package;
use Pickle\Package\JSON\Dumper;

abstract class AbstractCommand
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

    protected abstract function prepare();

    protected function fetch($target, $no_convert)
    {

    }

    protected function extConfigIsIn($path)
    {
        /* XXX implement config*.(m4|w32) search for the case it's somewhere in the subdir,
            in that case we can take that subdir as the extension root. */
        if (defined('PHP_WINDOWS_VERSION_MAJOR') !== false) {
                return file_exists(realpath($path) . DIRECTORY_SEPARATOR . "config.w32");
        } else {
            $r = glob("$path/config*.m4");

            return (is_array($r) && !empty($r));
        }
    }

    public function execute($target, $no_convert)
    {
        $jsonLoader = new Package\JSON\Loader(new Package\Loader());
        $pickle_json = $target . DIRECTORY_SEPARATOR . 'pickle.json';
        $package = NULL;

        if (file_exists($pickle_json)) {
            $package = $jsonLoader->load($pickle_json);
        }

        if (null === $package && $no_convert) {
            throw new \RuntimeException('XML package are not supported. Please convert it before install');
        }

        if (null === $package) {
            if (file_exists($target . DIRECTORY_SEPARATOR . 'package2.xml')) {
                $pkg_xml = $target . DIRECTORY_SEPARATOR . 'package2.xml';
            } else if (file_exists($target . DIRECTORY_SEPARATOR . 'package.xml')) {
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
