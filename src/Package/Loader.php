<?php
namespace Pickle\Package;

use Composer\Package\Loader\LoaderInterface;
use Composer\Package\Version\VersionParser;

class Loader implements LoaderInterface
{
    protected $versionParser;
    protected $loadOptions;

    public function __construct(VersionParser $parser = null, $loadOptions = false)
    {
        $this->versionParser = $parser ?: new VersionParser();
        $this->loadOptions = $loadOptions;
    }

    public function load(array $config, $class = 'Pickle\Package')
    {
        $version = $this->versionParser->normalize($config['version']);

        $package = new $class($config['name'], $version, $config['version']);
        $package->setType('extension');

        if (isset($config['stability'])) {
            $package->setStability($config['stability']);
        }

        if (isset($config['extra']) && is_array($config['extra'])) {
            $package->setExtra($config['extra']);
        }

        if (isset($config['source'])) {
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

        if (isset($config['dist'])) {
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

        if (!empty($config['time'])) {
            $time = ctype_digit($config['time']) ? '@'.$config['time'] : $config['time'];

            try {
                $date = new \DateTime($time, new \DateTimeZone('UTC'));
                $package->setReleaseDate($date);
            } catch (\Exception $e) {
            }
        }

        if (!empty($config['description']) && is_string($config['description'])) {
            $package->setDescription($config['description']);
        }

        if (!empty($config['homepage']) && is_string($config['homepage'])) {
            $package->setHomepage($config['homepage']);
        }

        if (!empty($config['keywords']) && is_array($config['keywords'])) {
            $package->setKeywords($config['keywords']);
        }

        if (!empty($config['license'])) {
            $package->setLicense(is_array($config['license']) ? $config['license'] : array($config['license']));
        }

        if (!empty($config['authors']) && is_array($config['authors'])) {
            $package->setAuthors($config['authors']);
        }

        if (isset($config['support'])) {
            $package->setSupport($config['support']);
        }

        return $package;
    }
}
