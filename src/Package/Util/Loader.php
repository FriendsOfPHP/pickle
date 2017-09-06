<?php

/**
 * Pickle
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2015-2015, Pickle community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Pickle\Package\Util;

use Composer\Package\Loader\LoaderInterface;
use Composer\Package\Version\VersionParser;
use Pickle\Base\Interfaces;
use Pickle\Package;

class Loader implements LoaderInterface
{
    protected $versionParser;
    protected $loadOptions;

    public function __construct(VersionParser $parser = null, $loadOptions = false)
    {
        $this->versionParser = $parser ?: new VersionParser();
        $this->loadOptions = $loadOptions;
    }

    /**
     * @param array  $config
     * @param string $package
     *
     * @return \Pickle\Base\Interfaces\Package $package
     */
    public function load(array $config, $package = 'Pickle\Base\Interfaces\Package')
    {
        if (isset($config['version'])) {
            $version = $this->versionParser->normalize($config['version']);
            $package = Package::factory($config['name'], $version, $config['version'], true);
        } else {
            $package = Package::factory($config['name'], '', '', true);
        }

        if (isset($config['type']) && $config['type'] != 'extension') {
            throw new \UnexpectedValueException($package->getName().' is not a extension(s) package');
        }
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

    protected function setPackageStability(Interfaces\Package $package, array $config)
    {
        if ($this->isValid($config, 'stability', 'string')) {
            $package->setStability($config['stability']);
        }
    }

    protected function setPackageExtra(Interfaces\Package $package, array $config)
    {
        if ($this->isValid($config, 'extra', 'array')) {
            $package->setExtra($config['extra']);
        }
    }

    protected function setPackageDescription(Interfaces\Package $package, array $config)
    {
        if ($this->isValid($config, 'description', 'string')) {
            $package->setDescription($config['description']);
        }
    }

    protected function setPackageHomepage(Interfaces\Package $package, array $config)
    {
        if ($this->isValid($config, 'homepage', 'string')) {
            $package->setHomepage($config['homepage']);
        }
    }

    protected function setPackageKeywords(Interfaces\Package $package, array $config)
    {
        if ($this->isValid($config, 'keywords', 'array')) {
            $package->setKeywords($config['keywords']);
        }
    }

    protected function setPackageLicense(Interfaces\Package $package, array $config)
    {
        if (!empty($config['license'])) {
            $package->setLicense(is_array($config['license']) ? $config['license'] : array($config['license']));
        }
    }

    protected function setPackageAuthors(Interfaces\Package $package, array $config)
    {
        if ($this->isValid($config, 'authors', 'array')) {
            $package->setAuthors($config['authors']);
        }
    }

    protected function setPackageSupport(Interfaces\Package $package, array $config)
    {
        if (isset($config['support'])) {
            $package->setSupport($config['support']);
        }
    }

    protected function isValid($config, $key, $type = 'any')
    {
        switch ($type) {
            case 'string':
                return isset($config[$key]) && !empty($config[$key]) && is_string($config[$key]);

            case 'array':
                return isset($config[$key]) && !empty($config[$key]) && is_array($config[$key]);
        }

        return false;
    }

    protected function setPackageSource(Interfaces\Package $package, array $config)
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

    protected function setPackageDist(Interfaces\Package $package, array $config)
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

    protected function setPackageReleaseDate(Interfaces\Package $package, array $config)
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

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
