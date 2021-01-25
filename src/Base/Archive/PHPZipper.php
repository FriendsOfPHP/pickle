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

namespace Pickle\Base\Archive;

use Pickle\Base\Interfaces;
use RuntimeException;
use ZipArchive;

class PHPZipper extends PHP implements Interfaces\Archive\Zipper
{
    /**
     * {@inheritDoc}
     *
     * @see \Pickle\Base\Interfaces\Archive\Zipper::__construct()
     */
    public function __construct(string $path, int $flags)
    {
        parent::__construct();
        switch ($flags) {
            case self::FLAG_OPEN:
                $this->open($path);
                break;
            case self::FLAG_CREATE:
                $this->create($path, false);
                break;
            case self::FLAG_CREATE_OVERWRITE:
                $this->create($path, true);
                break;
            default:
                throw new RuntimeException('Invalid value of $flags in ' . __METHOD__);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @see \Pickle\Base\Interfaces\Archive\Zipper::__destruct()
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * {@inheritDoc}
     *
     * @see \Pickle\Base\Interfaces\Archive\Zipper::addFromString($localname, $contents)
     */
    public function addFromString(string $localname, string $contents): void
    {
        $localname = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $localname), '/');
        if ($this->zipArchive->addFromString($localname, $contents) !== true) {
            $error = 'Failed to add a file to the ZIP archive starting from its contents';
            $details = (string) $this->zipArchive->getStatusString();
            if ($details !== '') {
                $error .= ": {$details}";
            }
            throw new RuntimeException($error);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @see \Pickle\Base\Interfaces\Archive\Zipper::addFileWithoutPath()
     */
    public function addFileWithoutPath(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException("Failed to find the file {$path}");
        }
        if (!is_readable($path)) {
            throw new RuntimeException("The file {$path} is not readable");
        }
        if ($this->zipArchive->addFile($path, basename($path)) !== true) {
            $error = "Failed to add the file {$path} to the ZIP archive";
            $details = (string) $this->zipArchive->getStatusString();
            if ($details !== '') {
                $error .= ": {$details}";
            }
            throw new RuntimeException($error);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function create(string $path, bool $overwrite): void
    {
        $flags = ZipArchive::CREATE;
        if ($overwrite === false) {
            if (file_exists($path)) {
                throw new RuntimeException("The ZIP archive {$path} already exists");
            }
        } else {
            $flags |= ZipArchive::OVERWRITE;
        }
        if ($this->zipArchive->open($path, $flags) !== true) {
            $error = "Failed to create the ZIP archive {$path}";
            $details = (string) $this->zipArchive->getStatusString();
            if ($details !== '') {
                $error .= ": {$details}";
            }
            throw new RuntimeException($error);
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
