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

namespace Pickle\Package\PHP\Command\Build;

use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;

class Unix extends Abstracts\Package\Build implements Interfaces\Package\Build
{
    public function prepare()
    {
        $this->phpize();
    }

    public function phpize()
    {
        $backCwd = getcwd();
        chdir($this->pkg->getSourceDir());

        $res = $this->runCommand('phpize');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('phpize failed');
        }
    }

    protected function prepareConfigOpts()
    {
        $configureOptions = '';
        foreach ($this->options as $name => $option) {
            if ('enable' === $option->type) {
                true === $option->input ? 'enable' : 'disable';
            } elseif ('disable' == $option->type) {
                false === $option->input ? 'enable' : 'disable';
            } elseif ('with' === $option->type) {
                if ($option->input == 'yes' || $option->input == '1' || $option->type === true) {
                    $configureOptions .= ' --with-'.$name;
                } elseif ($option->input == 'no' || $option->input == '0' || $option->type === false) {
                    $configureOptions .= ' --without-'.$name;
                } else {
                    $configureOptions .= ' --with-'.$name.'='.$option->input;
                }
            }
        }

        $this->appendPkgConfigureOptions($configureOptions);

        return $configureOptions;
    }

    public function configure($opts = null)
    {
        $backCwd = getcwd();
        chdir($this->tempDir);

        /* XXX check sanity */
        $configureOptions = $opts ? $opts : $this->prepareConfigOpts();

        $res = $this->runCommand($this->pkg->getSourceDir().'/configure '.$configureOptions);
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('configure failed, see log at '.$this->tempDir.'\config.log');
        }
    }

    public function make()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $res = $this->runCommand('make');
        chdir($backCwd);

        if (!$res) {
            throw new \Exception('make failed');
        }
    }

    public function install()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $res = $this->runCommand('make install');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('make install failed');
        }
    }

    public function getInfo()
    {
        /* XXX implementat it */
        return array();
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
