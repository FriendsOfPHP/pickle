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

class Factory
{
    /**
     * Get the fully-qualified name of a class implementing the Zipper interface
     *
     * @throws \RuntimeException if no zipper is available.
     */
    public static function getZipperClassName(): string
    {
        if (static::isInfozipZipAvailable()) {
            return InfozipZipper::class;
        }
        if (static::isZipExtensionAvailable()) {
            return PHPZipper::class;
        }
        throw new RuntimeException(self::buildErrorMessage('zip'));
    }

    /**
     * Get the fully-qualified name of a class implementing the Unzipper interface
     *
     * @throws \RuntimeException if no zipper is available.
     */
    public static function getUnzipperClassName(): string
    {
        if (static::isInfozipUnzipAvailable()) {
            return InfozipUnzipper::class;
        }
        if (static::isZipExtensionAvailable()) {
            return PHPUnzipper::class;
        }
        throw new RuntimeException(self::buildErrorMessage('unzip'));
    }

    public static function isZipExtensionAvailable(): bool
    {
        return extension_loaded('zip');
    }

    public static function isInfozipZipAvailable(): bool
    {
        $output = [];
        $rc = -1;
        exec('zip -v 2>&1', $output, $rc);
        return $rc === 0 && preg_match('/info.?zip/i', $output[0] ?? '');
    }

    public static function isInfozipUnzipAvailable(): bool
    {
        $output = [];
        $rc = -1;
        exec('unzip -v 2>&1', $output, $rc);
        return $rc === 0 && preg_match('/info.?zip/i', $output[0] ?? '');
    }

    private static function buildErrorMessage(string $command): string
    {
        $message = <<<EOT
No support for ZIP files has been detected.
You have two options:
1. enable the zip PHP extension
2. install the {$command} system command

EOT
        ;
        if (DIRECTORY_SEPARATOR === '\\') {
            $message .= "You can find the Windows {$command}.exe program at the following address:\nftp://ftp.info-zip.org/pub/infozip/win32/";
        } else {
            $message .= <<<EOT
For example, to install the {$command} command you may try to run
apt-get install {$command}
or
apk add {$command}
EOT
            ;
        }

        return $message;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
