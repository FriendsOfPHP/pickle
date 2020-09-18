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

namespace Pickle\Package\Convey\Command;

use Pickle\Config;
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
            $status = isset($http_response_header[0]) ? $http_response_header[0] : "";
            if (strpos($status, '404') !== false) {
                throw new \Exception("cannot find $this->name");
            } else {
                if ($status) {
                    throw new \Exception("http error while loading informatio for $this->name: ".$status);
                } else {
                    throw new \Exception("http error while loading informatio for $this->name: unknown error");
                }
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

    public function execute($target, $no_convert, $versionOverrideOverride)
    {
        $this->fetch($target);

        $exe = DefaultExecutor::factory($this);

        return $exe->execute($target, $no_convert, $versionOverrideOverride);
    }

    public function getType()
    {
        return Type::GIT;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
