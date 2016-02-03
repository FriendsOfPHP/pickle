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

namespace Pickle\Package\PHP\Util;

use Pickle\Package;
use Pickle\Package\Util\JSON\Dumper;
use Pickle\Package\Util\Header;

class PackageXml
{
    protected $xmlPath = null;
    protected $jsonPath = null;
    protected $package = null;

    public function __construct($path)
    {
        $names = array(
                        'package2.xml',
                        'package.xml',
                );

        foreach ($names as $fl) {
            $xml = $path.DIRECTORY_SEPARATOR.$fl;
            if (true === is_file($xml)) {
                $this->xmlPath = $xml;
                break;
            }
        }

        if (!$this->xmlPath) {
            throw new \InvalidArgumentException("The path '$path' doesn't contain package.xml");
        }

        $this->jsonPath = $path.DIRECTORY_SEPARATOR.'composer.json';
    }

    public function load()
    {
        $loader = new Package\PHP\Util\XML\Loader(new Package\Util\Loader());
        $this->package = $loader->load($this->xmlPath);

        if (!$this->package) {
            throw new \Exception("Failed to load '{$this->xmlPath}'");
        }

        return $this->package;
    }

    public function convertChangeLog()
    {
        if (!$this->package) {
            $this->load();
        }

        $convertCl = new ConvertChangeLog($this->xmlPath);
        $convertCl->parse();
        $convertCl->generateReleaseFile();
    }

    /* XXX maybe need a separate composer.json util */
    public function dump($fname = null)
    {
        if (!$this->package) {
            $this->load();
        }

        if ($fname) {
            $this->jsonPath = $fname;
        }

        $version = new Header\Version($this->package);
        if ($version != $this->package->getPrettyVersion()) {
            throw new \Exception("Version mismatch - '".$version."' != '".$this->package->getVersion().'. in source vs JSON');
        }

        $dumper = new Dumper();
        $dumper->dumpToFile($this->package, $this->jsonPath, false);
    }

    public function getPackage()
    {
        if (!$this->package) {
            $this->load();
        }

        return $this->package;
    }

    public function getXmlPath()
    {
        return $this->xmlPath;
    }

    public function getJsonPath()
    {
        return $this->jsonPath;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
