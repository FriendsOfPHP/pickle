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

namespace Pickle\Package\PHP\Convey\Command;

use Exception;
use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;
use Pickle\Config;
use Pickle\Downloader\PECLDownloader;
use Pickle\Engine;
use Pickle\Package\Convey\Command\Type;

class Pecl extends Abstracts\Package\Convey\Command implements Interfaces\Package\Convey\Command
{
    public function execute($target, $no_convert, $versionOverrideOverride)
    {
        $this->fetch($target);

        $exe = new DefaultExecutor($this);

        return $exe->execute($target, $no_convert, $versionOverrideOverride);
    }

    public function getType()
    {
        return Type::PECL;
    }

    protected function prepare()
    {
        $engine = Engine::factory();

        if (Type::determinePecl($this->path, $matches) < 1) {
            throw new Exception('Not valid PECL URI');
        }

        if ($engine->getName() != 'php') {
            throw new Exception('PECL is only supported with PHP');
        }

        $this->name = $matches['package'];
        $this->url = 'https://pecl.php.net/get/' . $matches['package'];

        if (isset($matches['stability']) && $matches['stability'] !== '') {
            $this->stability = $matches['stability'];
            $this->url .= '-' . $matches['stability'];
        } else {
            $this->stability = 'stable';
        }

        if (isset($matches['version']) && $matches['version'] !== '') {
            $this->url .= '/' . $matches['version'];
            $this->prettyVersion = $matches['version'];
            $this->version = $matches['version'];
        } else {
            $this->version = 'latest';
            $this->prettyVersion = 'latest-' . $this->stability;
        }
    }

    protected function fetch($target)
    {
        $package = \Pickle\Package::factory($this->name, $this->version, $this->prettyVersion);
        $package->setDistUrl($this->url);

        $package->setRootDir($target);

        $downloader = new PECLDownloader($this->io, new Config());
        if ($downloader !== null) {
            $downloader->download($package, $target);
        }

        unset($package, $downloader);
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
