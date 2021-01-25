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

namespace Pickle\Base\Util;

use InvalidArgumentException;
use Pickle\Base\Interfaces;
use SplFileInfo;

class GitIgnore
{
    protected $excluded = [];

    public function __construct(Interfaces\Package $package)
    {
        $dir = $package->getSourceDir();
        $path = $package->getSourceDir() . '/.gitignore';
        $this->excluded = glob("{$dir}/.git/*");

        $this->excluded = [
            "{$dir}/.git/", "{$dir}/.gitignore", "{$dir}/.gitmodules",
        ];
        if (is_file($path) === false) {
            throw new InvalidArgumentException('File not found: ' . $path);
        }

        foreach (file($path) as $line) {
            $line = trim($line);

            // empty line or comment
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // negated glob
            if ($line[0] === '!') {
                $line = substr($line, 1);
                $files = array_diff(glob("{$dir}/*"), glob("{$dir}/{$line}"));
            // normal glob
            } else {
                $files = [];

                if (substr($line, -1) !== '/') {
                    $files = glob("{$dir}/{$line}");

                    $line .= '/';
                }

                $files = array_merge(glob("{$dir}/{$line}*"), $files);
            }

            $this->excluded = array_merge($this->excluded, $files);
        }
    }

    public function __invoke(SplFileInfo $file)
    {
        return $this->isExcluded($file) === false;
    }

    public function isExcluded(SplFileInfo $file)
    {
        foreach ($this->excluded as $path) {
            if (!strncmp($file, $path, strlen($path))) {
                return true;
            }
        }

        return false;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
