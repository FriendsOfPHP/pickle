<?php

/*
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

namespace Pickle\Package\PHP\Command;

use Closure;
use Composer\Package\Version\VersionParser;
use Phar;
use PharData;
use Pickle\Base\Interfaces;
use Pickle\Package\PHP\Util\PackageJson;
use Pickle\Package\Util\Header;

class Release implements Interfaces\Package\Release
{
    /**
     * @var \Pickle\Base\Interfaces\Package
     */
    protected $pkg;

    /**
     * @var Closure
     */
    protected $cb;

    /**
     * @var bool
     */
    protected $noConvert = false;

    /**
     * Constructor.
     *
     * @param string $path
     * @param Closure $cb
     * @param bool $noConvert
     */
    public function __construct($path, $cb = null, $noConvert = false)
    {
        $this->pkg = $this->readPackage($path);
        $this->cb = $cb;
        $this->noConvert = $noConvert;
    }

    /**
     * Create package.
     */
    public function create(array $args = [])
    {
        $archBasename = $this->pkg->getSimpleName() . '-' . $this->pkg->getPrettyVersion();

        /* Work around bug  #67417 [NEW]: ::compress modifies archive basename
        creates temp file and rename it */
        $tempName = getcwd() . '/pkl-tmp.tar';
        if (file_exists($tempName)) {
            unlink($tempName);
        }
        $arch = new PharData($tempName);
        $pkgDir = $this->pkg->getRootDir();

        foreach ($this->pkg->getFiles() as $file) {
            if (is_file($file)) {
                $name = str_replace($pkgDir, '', $file);
                $arch->addFile($file, $name);
            }
        }
        if (file_exists($tempName)) {
            @unlink($tempName . '.gz');
        }
        $arch->compress(Phar::GZ);
        unset($arch);

        rename($tempName . '.gz', $archBasename . '.tgz');
        unlink($tempName);

        if ($this->cb) {
            $cb = $this->cb;
            $cb($this->pkg);
        }
    }

    public function packLog()
    {
        /* pass, no logging seems to be happening here yet */
    }

    protected function readPackage($path)
    {
        $package = PackageJson::readPackage($path, $this->noConvert);
        $package->setRootDir(realpath($path));

        /* We're not adding any versions into the composer.json for the source release.
           Instead we just set the package version and that's it. The version is to be
           contained in the extension sources, so no need to maintain it more than once.
           */
        $version = new Header\Version($package);
        $package->replaceVersion((new VersionParser())->normalize($version), $version);

        return $package;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
