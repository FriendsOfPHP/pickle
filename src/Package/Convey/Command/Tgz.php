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

use Composer\Config;
use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;
use Pickle\Package;
use Pickle\Downloader\TGZDownloader;

class Tgz extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    protected function prepare()
    {
        $this->name = basename($this->path);
        $this->version = 'unknown';
        $this->prettyVersion = 'unknown';
        $this->url = $this->path;
    }

    protected function fetch($target)
    {
        $package = Package::factory($this->name, $this->version, $this->prettyVersion);

        $package->setDistUrl($this->url);
        $package->setRootDir($target);

        $downloader = new TGZDownloader($this->io, new Config());
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
        return Type::TGZ;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
