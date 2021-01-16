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

namespace Pickle\Base\Archive;

use RuntimeException;
use ZipArchive;

abstract class PHP
{
    /**
     * @var \ZipArchive
     */
    protected $zipArchive;

    protected function __construct()
    {
        $this->zipArchive = new ZipArchive();
    }

    /**
     * @throws \RuntimeException
     */
    protected function open(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException("Unable to find the file {$path}");
        }
        if (!is_readable($path)) {
            throw new RuntimeException("The ZIP archive {$path} is not readable");
        }
        if ($this->zipArchive->open($path) !== true) {
            $error = "Failed to open the ZIP archive {$path}";
            $details = (string) $this->zipArchive->getStatusString();
            if ($details !== '') {
                $error .= ": {$details}";
            }
            throw new RuntimeException($error);
        }
    }

    public function __destruct()
    {
        set_error_handler(static function() {}, -1);
        $this->zipArchive->close();
        restore_error_handler();
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
