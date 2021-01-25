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
use Pickle\Base\Util\FileOps;
use RuntimeException;
use Throwable;

class InfozipZipper extends Infozip implements Interfaces\Archive\Zipper
{
    use FileOps;

    /**
     * {@inheritDoc}
     *
     * @see \Pickle\Base\Interfaces\Archive\Zipper::__construct()
     */
    public function __construct(string $path, int $flags)
    {
        parent::__construct($path);
        switch ($flags) {
            case self::FLAG_OPEN:
                $this->checkExisting();
                break;
            case self::FLAG_CREATE:
                $this->create(false);
                break;
            case self::FLAG_CREATE_OVERWRITE:
                $this->create(true);
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
        $this->cleanup();
    }

    /**
     * {@inheritDoc}
     *
     * @see \Pickle\Base\Interfaces\Archive\Zipper::addFromString($localname, $contents)
     */
    public function addFromString(string $localname, string $contents): void
    {
        $localname = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $localname), '/');
        $this->createTempDir();
        $tempDir = $this->getTempDir();
        $dirname = dirname($localname);
        if ($dirname !== '' && $dirname !== '.') {
            if (mkdir($tempDir . '/' . $dirname, 0777, true) !== true) {
                throw new RuntimeException('Failed to create a temporary directory');
            }
        }
        if (file_put_contents($tempDir . '/' . $localname, $contents) === false) {
            throw new RuntimeException('Failed to write a temporary file');
        }
        $originalCWD = getcwd();
        if (chdir($tempDir) !== true) {
            throw new RuntimeException("Failed to enter directory {$tempDir}");
        }
        try {
            $this->run('zip', [
                escapeshellarg($this->path),
                escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, $localname)),
            ]);
        } finally {
            chdir($originalCWD);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @see \Pickle\Base\Interfaces\Archive\Zipper::addFileWithoutPath()
     */
    public function addFileWithoutPath(string $path): void
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (!is_file($path)) {
            throw new RuntimeException("Failed to find the file {$path}");
        }
        if (!is_readable($path)) {
            throw new RuntimeException("The file {$path} is not readable");
        }
        $this->run('zip', [
            '-j',
            escapeshellarg($this->path),
            escapeshellarg($path),
        ]);
    }

    private function create(bool $overwrite): void
    {
        if (file_exists($this->path)) {
            if ($overwrite === false) {
                throw new RuntimeException("The ZIP archive {$this->path} already exists");
            }
            if (unlink($this->path) === false) {
                throw new RuntimeException("Failed to overwrite the ZIP archive {$this->path}");
            }
        }
        $this->run([
            '-j1',
            escapeshellarg($this->path),
            escapeshellarg(__FILE__),
        ]);
        try {
            $this->run([
                '-d',
                escapeshellarg($this->path),
                escapeshellarg(basename(__FILE__)),
            ]);
        } catch (Throwable $x) {
            unlink($this->path);
            throw $x;
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
