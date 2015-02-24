<?php
namespace Pickle\Package\PHP\Util;

use Composer\Package\Loader\LoaderInterface;
use Composer\Package\Version\VersionParser;
use Pickle\Package\PHP;

class Loader implements LoaderInterface
{
    protected $versionParser;
    protected $loadOptions;

    public function __construct(VersionParser $parser = null, $loadOptions = false)
    {
        $this->versionParser = $parser ?: new VersionParser();
        $this->loadOptions = $loadOptions;
    }

    public function load(array $config, $package = 'Pickle\Package\PHP\Package')
    {
        $version = $this->versionParser->normalize($config['version']);

        $package = new PHP\Package($config['name'], $version, $config['version']);
        $package->setType('extension');

        $this->setPackageSource($package, $config);
        $this->setPackageDist($package, $config);
        $this->setPackageReleaseDate($package, $config);
        $this->setPackageStability($package, $config);
        $this->setPackageExtra($package, $config);
        $this->setPackageDescription($package, $config);
        $this->setPackageHomepage($package, $config);
        $this->setPackageKeywords($package, $config);
        $this->setPackageLicense($package, $config);
        $this->setPackageAuthors($package, $config);
        $this->setPackageSupport($package, $config);

        return $package;
    }

    protected function setPackageStability(PHP\Package $package, array $config)
    {
        if ($this->isValid($config, "stability", "string")) {
            $package->setStability($config['stability']);
        }
    }

    protected function setPackageExtra(PHP\Package $package, array $config)
    {
        if ($this->isValid($config, "extra", "array")) {
            $package->setExtra($config['extra']);
        }
    }

    protected function setPackageDescription(PHP\Package $package, array $config)
    {
        if ($this->isValid($config, "description", "string")) {
            $package->setDescription($config['description']);
        }
    }

    protected function setPackageHomepage(PHP\Package $package, array $config)
    {
        if ($this->isValid($config, "homepage", "string")) {
            $package->setHomepage($config['homepage']);
        }
    }

    protected function setPackageKeywords(PHP\Package $package, array $config)
    {
        if ($this->isValid($config, "keywords", "array")) {
            $package->setKeywords($config['keywords']);
        }
    }

    protected function setPackageLicense(PHP\Package $package, array $config)
    {
        if (!empty($config['license'])) {
            $package->setLicense(is_array($config['license']) ? $config['license'] : array($config['license']));
        }
    }

    protected function setPackageAuthors(PHP\Package $package, array $config)
    {
        if ($this->isValid($config, "authors", "array")) {
            $package->setAuthors($config['authors']);
        }
    }

    protected function setPackageSupport(PHP\Package $package, array $config)
    {
        if (isset($config['support'])) {
            $package->setSupport($config['support']);
        }
    }

    protected function isValid($config, $key, $type = "any")
    {
        switch ($type) {
            case "string":
                return (isset($config[$key]) && !empty($config[$key]) && is_string($config[$key]));

            case "array":
                return (isset($config[$key]) && !empty($config[$key]) && is_array($config[$key]));
        }

        return false;
    }

    protected function setPackageSource(PHP\Package $package, array $config)
    {
        if (!isset($config['source'])) {
            return;
        }

        if (!isset($config['source']['type']) || !isset($config['source']['url']) || !isset($config['source']['reference'])) {
            throw new \UnexpectedValueException(sprintf(
                "Package %s's source key should be specified as {\"type\": ..., \"url\": ..., \"reference\": ...},\n%s given.",
                $config['name'],
                json_encode($config['source'])
            ));
        }
        $package->setSourceType($config['source']['type']);
        $package->setSourceUrl($config['source']['url']);
        $package->setSourceReference($config['source']['reference']);
        if (isset($config['source']['mirrors'])) {
            $package->setSourceMirrors($config['source']['mirrors']);
        }
    }

    protected function setPackageDist(PHP\Package $package, array $config)
    {
        if (!isset($config['dist'])) {
            return;
        }

        if (!isset($config['dist']['type'])
            || !isset($config['dist']['url'])) {
            throw new \UnexpectedValueException(sprintf(
                "Package %s's dist key should be specified as ".
                "{\"type\": ..., \"url\": ..., \"reference\": ..., \"shasum\": ...},\n%s given.",
                $config['name'],
                json_encode($config['dist'])
            ));
        }

        $package->setDistType($config['dist']['type']);
        $package->setDistUrl($config['dist']['url']);
        $package->setDistReference(isset($config['dist']['reference']) ? $config['dist']['reference'] : null);
        $package->setDistSha1Checksum(isset($config['dist']['shasum']) ? $config['dist']['shasum'] : null);
        if (isset($config['dist']['mirrors'])) {
            $package->setDistMirrors($config['dist']['mirrors']);
        }
    }

    protected function setPackageReleaseDate(PHP\Package $package, array $config)
    {
        if (empty($config['time'])) {
            return;
        }

        $time = ctype_digit($config['time']) ? '@'.$config['time'] : $config['time'];

        try {
            $date = new \DateTime($time, new \DateTimeZone('UTC'));
            $package->setReleaseDate($date);
        } catch (\Exception $e) {
            // don't crash if time is incorrect
        }
    }
}
