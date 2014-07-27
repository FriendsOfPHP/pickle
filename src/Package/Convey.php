<?php

namespace Pickle\Package;

use Pickle\Package;
use Pickle\Package\JSON\Dumper;
use Pickle\Downloader\PECLDownloader;
use Pickle\Downloader\TGZDownloader;
use Composer\Config;
use Composer\IO\ConsoleIO;
use Composer\Downloader\GitDownloader;
use Composer\Downloader\ArchiveDownloader;

class Convey
{
    const PKG_TYPE_PECL = 0;
    const PKG_TYPE_GIT = 1;
    const PKG_TYPE_TGZ = 2;
    const PKG_TYPE_SRC_DIR = 3;
    const PKG_TYPE_ANY = 42;

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
        if (!$path) {
            throw new \Exception("Path cannot be empty");
        }

        $this->path = $path;
        $this->io = $io;
        $this->type = self::PKG_TYPE_ANY;

        $this->prepare();
    }

    protected function haveRemoteOrigin()
    {
        return (false === realpath($this->path));
    }

    protected function prepare()
    {
        if ($this->haveRemoteOrigin() && preg_match(self::RE_PECL_PACKAGE, $this->path, $matches) > 0) {
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
        } else if ($this->haveRemoteOrigin() && preg_match(self::RE_GIT_PACKAGE, $this->path, $matches) > 0) {
            $this->type = self::PKG_TYPE_GIT;
            $this->name = $matches['package'];
            $this->version = isset($matches['reference']) ? $matches['reference'] : 'master';
            $this->prettyVersion = $this->version;
            $this->url = preg_replace('/#.*$/', '', $this->path);
        } else if ('.tgz' == substr($this->path, -4) || '.tar.gz' == substr($this->path, -7)) {
            $this->type = self::PKG_TYPE_TGZ;
            $this->name = basename($this->path);
            $this->version = "unknown";
            $this->prettyVersion = "unknown";
            $this->url = $this->path;
        } else if (!$this->haveRemoteOrigin() && is_dir($this->path)) {
            $this->type = self::PKG_TYPE_SRC_DIR;
            $this->url = $this->path;
            /* pass */
        } else {
            throw new \Exception("Unable to handle this kind of origin");
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

            case self::PKG_TYPE_TGZ:
                return new TGZDownloader($this->io, new Config());
                break;

            case self::PKG_TYPE_ANY:
            default:
                /* XXX */
                return NULL;
                break;
                
        }
    }

    protected function extConfigIsInPath()
    {
        /* XXX implement config*.(m4|w32) search for the case it's somewhere in the subdir,
            in that case we can take that subdir as the extension root. */
        if (defined('PHP_WINDOWS_VERSION_MAJOR') !== false) {
                return file_exists(realpath($this->path) . DIRECTORY_SEPARATOR . "config.w32");
        } else {
            $r = glob("{$this->path}/config*.m4");

            return (is_array($r) && !empty($r));
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
            case self::PKG_TYPE_TGZ:
                $package = new Package($this->name, $this->version, $this->prettyVersion);

                if (self::PKG_TYPE_GIT == $this->type) {
                    $package->setSourceType('git');
                    $package->setSourceUrl($this->url);
                    $package->setSourceReference($this->version);
                } else if (self::PKG_TYPE_PECL == $this->type || self::PKG_TYPE_TGZ == $this->type) {
                    $package->setDistUrl($this->url);
                }
                $package->setRootDir($target);

                $downloader = $this->getDownloader();
                if (null !== $downloader) {
                    $downloader->download($package, $target);
                }

                break;

            case self::PKG_TYPE_SRC_DIR:
                if ($this->extConfigIsInPath()) {
                    $target = realpath($this->path);
                }

            case self::PKG_TYPE_ANY:
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

