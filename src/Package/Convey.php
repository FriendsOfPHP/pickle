<?php

namespace Pickle\Package;

use Pickle\Package;
use Pickle\Package\JSON\Dumper;
use Pickle\Downloader\PECLDownloader;
use Composer\Config;
use Composer\IO\ConsoleIO;
use Composer\Downloader\GitDownloader;
use Composer\Downloader\ArchiveDownloader;

class Convey
{
    const PKG_TYPE_PECL = 0;
    const PKG_TYPE_GIT = 1;
    const PKG_TYPE_ANY = 2;

    const RE_PECL_PACKAGE = '#^
        (?:pecl/)?
        (?<package>\w+)
        (?:
            \-(?<stability>beta|stable|alpha)
            |@(?<version>(?:\d+(?:\.\d+){1,2})|(?:[1-2]\d{3}[0-1]\d[0-3]\d{1}))
        )?
    $#x';

    const RE_GIT_PACKAGE = '#^
        (?:git|https?)://.*?/
        (?P<package>\w+)
        (?:
            (?:\.git|)
            (?:\#(?P<reference>.*?)|)
        )?
    $#x';

    protected $io;
    protected $type;
    protected $path;
    protected $version;
    protected $prettyVersion;
    protected $name;
    protected $stability;
    protected $url;

    public function __construct($path, ConsoleIO $io)
    {
        $this->path = $path;
        $this->io = $io;
        $this->type = self::PKG_TYPE_ANY;

        $this->prepare();
    }

    protected function prepare()
    {
        if (preg_match(self::RE_PECL_PACKAGE, $this->path, $matches) > 0) {
            $this->type = self::PKG_TYPE_PECL;
            $this->name = $matches['package'];
            $this->url = 'http://pecl.php.net/get/' . $matches['package'];

            if (isset($matches['stability']) && '' !== $matches['stability']) {
                $this->stability = $matches['stability'];
                $this->url .= '-' . $matches['stability'];
            } else {
                $this->stability = 'stable';
            }

            if (isset($matches['version']) && '' !== $matches['version']) {
                $this->url .= '/' . $matches['version'];
                $this->prettyVersion = $matches['version'];
                $this->version = $matches['version'];
            } else {
                $this->version = 'latest';
                $this->prettyVersion = 'latest-' . $this->stability;
            }
        } else if (preg_match(self::RE_GIT_PACKAGE, $this->path, $matches) > 0) {
            $this->type = self::PKG_TYPE_GIT;
            $this->name = $matches['package'];
            $this->version = isset($matches['reference']) ? $matches['reference'] : 'master';
            $this->prettyVersion = $this->version;
            $this->url = preg_replace('/#.*$/', '', $this->path);
        }
    }

    protected function getDownloader()
    {
        /* XXX this is kinda downloader factory, lets see if it make sense to move into a Pickle\Downloader\Factory */
        switch ($this->type) {
            case self::PKG_TYPE_PECL:
                return new PECLDownloader($this->io, new Config());
                break;

            case self::PKG_TYPE_GIT:
                return new GitDownloader($this->io, new Config());
                break;

            case self::PKG_TYPE_ANY:
            default:
                /* XXX */
                return NULL;
                break;
                
        }
    }

    public function deliver($target = "", $no_convert = false)
    {
        $target = $target ? realpath($target) : sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->name;

        /* This package is a dummy one, the real one will be loaded from the json conversion.
            But we need this step to get the package source. */
        switch ($this->type) {
            case self::PKG_TYPE_PECL:
            case self::PKG_TYPE_GIT:
                $package = new Package($this->name, $this->version, $this->prettyVersion);

                if (self::PKG_TYPE_GIT == $this->type) {
                    $package->setSourceType('git');
                    $package->setSourceUrl($this->url);
                    $package->setSourceReference($this->version);
                } else if (self::PKG_TYPE_PECL == $this->type) {
                    $package->setDistUrl($this->url);
                }
                $package->setRootDir($target);

                $downloader = $this->getDownloader();
                if (null !== $downloader) {
                    $downloader->download($package, $target);
                }

                break;

            case self::PKG_TYPE_ANY:
                /* this might be wrong, assumed that the target is a dir and already contains source */
                $target = realpath($this->path);

            default:
                    
                /* XXX */

                break;
        }

        /* Now the real work is going, load the pickle.json or convert package.xml and create the package */
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
}

