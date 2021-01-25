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

namespace Pickle\Package\PHP\Util;

use InvalidArgumentException;
use RuntimeException;
use StdClass;

class ConvertChangeLog
{
    private $path;

    private $changelog;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        if (is_file($path) === false) {
            throw new InvalidArgumentException('File not found: ' . $path);
        }

        $this->path = $path;
    }

    public function parse()
    {
        $xml = @simplexml_load_file($this->path);

        $changelog = [];
        $current = new StdClass();
        $current->date = $xml->date;
        $current->time = $xml->time;
        $current->version = new StdClass();
        $current->version->release = $xml->version->release;
        $current->stability = new StdClass();
        $current->stability->release = $xml->stability->release;
        $current->notes = $xml->notes;

        $changelog[] = $current;
        if (isset($xml->changelog->release)) {
            foreach ($xml->changelog->release as $release) {
                $changelog[] = $release;
            }
        }
        $this->changelog = $changelog;
    }

    public function generateReleaseFile()
    {
        if (empty($this->changelog)) {
            return;
        }

        $contents = '';
        foreach ($this->changelog as $cl) {
            $contents .= 'Version: ' . $cl->version->release . "\n"
                     . 'Date: ' . $cl->date . ' ' . $cl->time . "\n"
                     . 'Stability: ' . $cl->stability->release . "\n"
                     . "\n"
                     . 'notes: ' . $cl->notes . "\n"
                     . "\n"
                     . "\n"
                     . "\n";
        }

        if (file_put_contents(dirname($this->path) . DIRECTORY_SEPARATOR . 'RELEASES', $contents) === false) {
            throw new RuntimeException('cannot save RELEASE file in <' . dirname($this->path) . DIRECTORY_SEPARATOR . 'RELEASES>');
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
