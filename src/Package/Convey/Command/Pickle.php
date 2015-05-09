<?php

namespace Pickle\Package\Convey\Command;

use Composer\Config;
use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;
use Pickle\Package;
use Composer\Downloader\GitDownloader;
use Composer\Package\Version\VersionParser;
use Composer\Package\LinkConstraint\VersionConstraint;

class Pickle extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    /**
     * @var string
     */
    protected $type;

    protected function fetchPackageJson()
    {
        $extensionJson = @file_get_contents('http://localhost:8080/json/'.$this->name.'.json');
        if (!$extensionJson) {
            if (strpos($http_response_header[0], '404') !== false) {
                throw new \Exception("cannot find $this->name");
            } else {
                throw new \Exception("http error while loading informatio for $this->name: ".$http_response_header[0]);
            }
        }

        return json_decode($extensionJson, true);
    }

    protected function prepare()
    {
        if (Type::determinePickle($this->path, $matches) < 1) {
            throw new \Exception('Not a pickle git URI');
        }

        $this->name = $matches['package'];

        $extension = $this->fetchPackageJson();

        $versionParser = new VersionParser();
        if ($matches['version'] == '') {
            $versions = array_keys($extension['packages'][$this->name]);
            if (count($versions) > 1) {
                $versionToUse = $versions[1];
            } else {
                $versionToUse = $versions[0];
            }
        } else {
            $versionConstraints = $versionParser->parseConstraints($matches['version']);

            /* versions are sorted decreasing */
            foreach ($extension['packages'][$this->name] as $version => $release) {
                $constraint = new VersionConstraint('=', $versionParser->normalize($version));
                if ($versionConstraints->matches($constraint)) {
                    $versionToUse = $version;
                    break;
                }
            }
        }

        $package = $extension['packages'][$this->name][$versionToUse];
        $this->version = $versionToUse;
        $this->normalizedVersion = $versionParser->normalize($versionToUse);

        $this->name = $matches['package'];
        $this->prettyVersion = $this->version;
        $this->url = $package['source']['url'];
        $this->reference = $package['source']['reference'];
        $this->type = $package['source']['type'];
    }

    protected function fetch($target)
    {
        $package = Package::factory($this->name, $this->version, $this->prettyVersion);

        $package->setSourceType($this->type);
        $package->setSourceUrl($this->url);
        $package->setSourceReference($this->version);
        $package->setRootDir($target);

        $downloader = new GitDownloader($this->io, new Config());
        if (null !== $downloader) {
            $downloader->download($package, $target);
        }
    }

    public function execute($target, $no_convert)
    {
        $this->fetch($target);

        $exe = DefaultExecutor::factory($this);

        return $exe->execute($target, $no_convert);
    }

    public function getType()
    {
        return Type::GIT;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
