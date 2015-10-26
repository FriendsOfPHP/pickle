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

namespace Pickle\Package\HHVM\Command\Build;

use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;

class Unix extends Abstracts\Package\Build implements Interfaces\Package\Build
{
    public function prepare()
    {
        $this->hphpize();
    }

    public function hphpize()
    {
        $newcwd = $this->pkg->getSourceDir();

        $res = $this->runCommand("cd $newcwd && hphpize");
        if (!$res) {
            throw new \Exception('hphpize failed');
        }
    }

    public function configure($opts = null)
    {
        $newcwd = $this->pkg->getSourceDir();

        $res = $this->runCommand("cd $newcwd && cmake .");
        if (!$res) {
            throw new \Exception('cmake failed');
        }
    }

    public function make()
    {
        $newcwd = $this->pkg->getSourceDir();

        $res = $this->runCommand("cd $newcwd && make");
        if (!$res) {
            throw new \Exception('make failed');
        }
    }

    public function install()
    {
        $newcwd = $this->pkg->getSourceDir();
        $res = $this->runCommand("cd $newcwd && make install");
        if (!$res) {
            throw new \Exception('make install failed');
        }

        $this->updateIni();
    }

    protected function updateIni()
    {
        $ini = \Pickle\Engine\Ini::factory(\Pickle\Engine::factory());
        $ini->updatePickleSection(array($this->pkg->getName()));
    }

    public function getInfo()
    {
        /* XXX implementat it */
        return array();
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
