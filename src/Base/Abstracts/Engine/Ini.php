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

namespace Pickle\Base\Abstracts\Engine;

use Exception;

class Ini
{
    public const PICKLE_HEADER = ';Pickle installed extension, do not edit this line and below';

    public const PICKLE_FOOTER = ';Pickle installed extension, do not edit this line and above';

    protected $engine;

    protected $path;

    protected $raw;

    protected $pickleHeaderStartPos = -1;

    protected $pickleHeaderEndPos = -1;

    protected $pickleFooterStartPos = -1;

    protected $pickleFooterEndPos = -1;

    public function __construct(\Pickle\Base\Interfaces\Engine $php)
    {
        $this->engine = $php;
        $this->path = $php->getIniPath();

        $this->raw = @file_get_contents($this->path);
        if ($this->raw === false) {
            throw new Exception('Cannot read php.ini');
        }
    }

    public function getengine()
    {
        return $this->engine;
    }

    protected function getPickleSection()
    {
        return substr($this->raw, $this->pickleHeaderEndPos, $this->pickleFooterStartPos - $this->pickleHeaderEndPos);
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
