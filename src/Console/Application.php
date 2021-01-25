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

namespace Pickle\Console;

use Exception;
use Phar;
use Pickle\Base\Archive;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public const NAME = 'pickle';

    public const VERSION = '@pickle-version@';

    public function __construct($name = null, $version = null)
    {
        self::checkExtensions();

        parent::__construct($name ?: static::NAME, $version ?: (static::VERSION === '@' . 'pickle-version@' ? 'source' : static::VERSION));
    }

    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        $helperSet->set(new Helper\PackageHelper());

        return $helperSet;
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new Command\ValidateCommand();
        $commands[] = new Command\ConvertCommand();
        $commands[] = new Command\ReleaseCommand();
        $commands[] = new Command\InstallerCommand();
        $commands[] = new Command\InfoCommand();

        if (Phar::running() !== '') {
            $commands[] = new Command\SelfUpdateCommand();
        }

        return $commands;
    }

    private static function checkExtensions()
    {
        $required_exts = [
            'zlib',
            'mbstring',
            'simplexml',
            'json',
            'dom',
            'openssl',
            'phar',
        ];

        foreach ($required_exts as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("Extension '{$ext}' required but not loaded, full required list: " . implode(', ', $required_exts));
            }
        }

        Archive\Factory::getUnzipperClassName();
        Archive\Factory::getZipperClassName();
    }
}
