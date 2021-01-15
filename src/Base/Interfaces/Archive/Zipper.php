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

namespace Pickle\Base\Interfaces\Archive;

interface Zipper
{
    /**
     * Instance creation flag: open an existing archive.
     *
     * @var int
     */
    public const FLAG_OPEN = 1;

    /**
     * Instance creation flag: create a new archive.
     *
     * @var int
     */
    public const FLAG_CREATE = 2;

    /**
     * Instance creation flag: create a new archive (overwriting an existing one if it already exists).
     *
     * @var int
     */
    public const FLAG_CREATE_OVERWRITE = 3;

    /**
     * Open an existing archive, or create a new one.
     *
     * @param string $path the path of the archive to be open/created
     * @param int $flag FLAG_OPEN to open an existing archive, FLAG_CREATE/FLAG_CREATE_OVERWRITE to create a new archive
     *
     * @throws \RuntimeException in case of errors
     */
    public function __construct(string $path, int $flags);

    /**
     * Add a file the archive, without saving the directory names, only the file name.
     *
     * @param string $path the path to the file to be saved
     */
    public function addFileWithoutPath(string $path): void;

    /**
     * Add a file the archive using its contents.
     *
     * @throws \RuntimeException in case of errors
     */
    public function addFromString(string $localname, string $contents): void;

    /**
     * Close the archive.
     */
    public function __destruct();
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
